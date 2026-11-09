<?php

declare(strict_types=1);

final class FluxoCaixa
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    // ==================================================
    // DASHBOARD COMPLETO
    // ==================================================

    public function dashboard(
        int $empresaId,
        array $filtros = []
    ): array {

        $this->validarEmpresa($empresaId);

        $filtros = $this->normalizarFiltros(
            $filtros
        );

        $disponibilidades = $this->disponibilidades(
            $empresaId,
            $filtros
        );

        $previsto = $this->resumoPrevisto(
            $empresaId,
            $filtros
        );

        $realizado = $this->resumoRealizado(
            $empresaId,
            $filtros
        );

        $saldoDisponivel =
            (float)$disponibilidades['saldo_total_caixas']
            + (float)$disponibilidades['saldo_total_bancos'];

        $resultadoPrevisto =
            (float)$previsto['entradas_previstas']
            - (float)$previsto['saidas_previstas'];

        $saldoPrevistoFinal =
            $saldoDisponivel
            + $resultadoPrevisto;

        return [
            'disponibilidades' => [
                'saldo_total_caixas' =>
                (float)$disponibilidades['saldo_total_caixas'],

                'saldo_total_bancos' =>
                (float)$disponibilidades['saldo_total_bancos'],

                'saldo_total_disponivel' =>
                $saldoDisponivel,

                'saldo_previsto_final' =>
                $saldoPrevistoFinal
            ],

            'previsto' => [
                'entradas_previstas' =>
                (float)$previsto['entradas_previstas'],

                'saidas_previstas' =>
                (float)$previsto['saidas_previstas'],

                'resultado_previsto' =>
                $resultadoPrevisto,

                'total_receber_vencido' =>
                (float)$previsto['total_receber_vencido'],

                'total_pagar_vencido' =>
                (float)$previsto['total_pagar_vencido'],

                'total_contas_vencidas' =>
                (float)$previsto['total_receber_vencido']
                    + (float)$previsto['total_pagar_vencido']
            ],

            'realizado' => [
                'entradas_realizadas' =>
                (float)$realizado['entradas_realizadas'],

                'saidas_realizadas' =>
                (float)$realizado['saidas_realizadas'],

                'resultado_realizado' =>
                (float)$realizado['entradas_realizadas']
                    - (float)$realizado['saidas_realizadas']
            ],

            'grafico' => $this->grafico(
                $empresaId,
                $filtros
            )
        ];
    }

    // ==================================================
    // DISPONIBILIDADES
    // ==================================================

    public function disponibilidades(
        int $empresaId,
        array $filtros = []
    ): array {

        $this->validarEmpresa($empresaId);

        $filtros = $this->normalizarFiltros(
            $filtros
        );

        $filialId = $filtros['filial_id'];

        /*
         * Saldo dos caixas.
         */

        $sqlCaixas = "
            SELECT
                COALESCE(
                    SUM(c.saldo_atual),
                    0
                )
            FROM caixas c
            WHERE c.empresa_id = :empresa_id
        ";

        $paramsCaixas = [
            ':empresa_id' => $empresaId
        ];

        if ($filialId !== null) {
            $sqlCaixas .= "
                AND c.filial_id = :filial_id
            ";

            $paramsCaixas[':filial_id'] =
                $filialId;
        }

        $stmt = $this->pdo->prepare(
            $sqlCaixas
        );

        $stmt->execute(
            $paramsCaixas
        );

        $saldoCaixas =
            (float)$stmt->fetchColumn();

        /*
         * Saldo dos bancos.
         */

        $sqlBancos = "
            SELECT
                COALESCE(
                    SUM(b.saldo_atual),
                    0
                )
            FROM bancos b
            WHERE b.empresa_id = :empresa_id
        ";

        $paramsBancos = [
            ':empresa_id' => $empresaId
        ];

        if ($filialId !== null) {
            $sqlBancos .= "
                AND b.filial_id = :filial_id
            ";

            $paramsBancos[':filial_id'] =
                $filialId;
        }

        $stmt = $this->pdo->prepare(
            $sqlBancos
        );

        $stmt->execute(
            $paramsBancos
        );

        $saldoBancos =
            (float)$stmt->fetchColumn();

        return [
            'saldo_total_caixas' =>
            $saldoCaixas,

            'saldo_total_bancos' =>
            $saldoBancos,

            'saldo_total_disponivel' =>
            $saldoCaixas + $saldoBancos
        ];
    }

    // ==================================================
    // LISTAR SALDOS DOS CAIXAS
    // ==================================================

    public function listarSaldosCaixas(
        int $empresaId,
        array $filtros = []
    ): array {

        $this->validarEmpresa($empresaId);

        $filtros = $this->normalizarFiltros(
            $filtros
        );

        $sql = "
            SELECT
                c.id,
                c.nome AS caixa,
                c.saldo_atual AS saldo,
                c.filial_id,

                COALESCE(
                    f.nome,
                    'Sem filial'
                ) AS filial,

                s.nome AS status

            FROM caixas c

            LEFT JOIN filiais f
                ON f.id = c.filial_id

            LEFT JOIN status s
                ON s.id = c.status_id

            WHERE c.empresa_id = :empresa_id
        ";

        $params = [
            ':empresa_id' => $empresaId
        ];

        if ($filtros['filial_id'] !== null) {
            $sql .= "
                AND c.filial_id = :filial_id
            ";

            $params[':filial_id'] =
                $filtros['filial_id'];
        }

        $sql .= "
            ORDER BY c.nome ASC
        ";

        $stmt = $this->pdo->prepare(
            $sql
        );

        $stmt->execute(
            $params
        );

        return $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );
    }

    // ==================================================
    // LISTAR SALDOS DOS BANCOS
    // ==================================================

    public function listarSaldosBancos(
        int $empresaId,
        array $filtros = []
    ): array {

        $this->validarEmpresa($empresaId);

        $filtros = $this->normalizarFiltros(
            $filtros
        );

        $sql = "
            SELECT
                b.id,
                b.nome_banco AS banco,
                b.conta,
                b.moeda,
                b.saldo_atual AS saldo,
                b.filial_id,

                COALESCE(
                    f.nome,
                    'Sem filial'
                ) AS filial,

                s.nome AS status

            FROM bancos b

            LEFT JOIN filiais f
                ON f.id = b.filial_id

            LEFT JOIN status s
                ON s.id = b.status_id

            WHERE b.empresa_id = :empresa_id
        ";

        $params = [
            ':empresa_id' => $empresaId
        ];

        if ($filtros['filial_id'] !== null) {
            $sql .= "
                AND b.filial_id = :filial_id
            ";

            $params[':filial_id'] =
                $filtros['filial_id'];
        }

        $sql .= "
            ORDER BY b.nome_banco ASC
        ";

        $stmt = $this->pdo->prepare(
            $sql
        );

        $stmt->execute(
            $params
        );

        return $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );
    }

    // ==================================================
    // RESUMO PREVISTO
    // ==================================================

    public function resumoPrevisto(
        int $empresaId,
        array $filtros = []
    ): array {

        $this->validarEmpresa($empresaId);

        $filtros = $this->normalizarFiltros(
            $filtros
        );

        $receber = $this->totalContasReceber(
            $empresaId,
            $filtros,
            false
        );

        $pagar = $this->totalContasPagar(
            $empresaId,
            $filtros,
            false
        );

        $receberVencido =
            $this->totalContasReceber(
                $empresaId,
                $filtros,
                true
            );

        $pagarVencido =
            $this->totalContasPagar(
                $empresaId,
                $filtros,
                true
            );

        return [
            'entradas_previstas' =>
            $receber,

            'saidas_previstas' =>
            $pagar,

            'total_receber_vencido' =>
            $receberVencido,

            'total_pagar_vencido' =>
            $pagarVencido
        ];
    }

    // ==================================================
    // RESUMO REALIZADO
    // ==================================================

    public function resumoRealizado(
        int $empresaId,
        array $filtros = []
    ): array {

        $this->validarEmpresa($empresaId);

        $filtros = $this->normalizarFiltros(
            $filtros
        );

        $realizados =
            $this->listarRealizado(
                $empresaId,
                $filtros
            );

        $entradas = 0.0;
        $saidas = 0.0;

        foreach ($realizados as $movimento) {
            $valor = (float)(
                $movimento['valor'] ?? 0
            );

            if (
                ($movimento['natureza'] ?? '')
                === 'ENTRADA'
            ) {
                $entradas += $valor;
            }

            if (
                ($movimento['natureza'] ?? '')
                === 'SAIDA'
            ) {
                $saidas += $valor;
            }
        }

        return [
            'entradas_realizadas' =>
            $entradas,

            'saidas_realizadas' =>
            $saidas
        ];
    }

    // ==================================================
    // LISTAR PREVISTO
    // ==================================================

    public function listarPrevisto(
        int $empresaId,
        array $filtros = []
    ): array {

        $this->validarEmpresa($empresaId);

        $filtros = $this->normalizarFiltros(
            $filtros
        );

        $dados = [];

        if (
            $filtros['origem'] === null
            || $filtros['origem']
            === 'CONTA_RECEBER'
        ) {
            $dados = array_merge(
                $dados,
                $this->listarContasReceberPrevistas(
                    $empresaId,
                    $filtros
                )
            );
        }

        if (
            $filtros['origem'] === null
            || $filtros['origem']
            === 'CONTA_PAGAR'
        ) {
            $dados = array_merge(
                $dados,
                $this->listarContasPagarPrevistas(
                    $empresaId,
                    $filtros
                )
            );
        }

        usort(
            $dados,
            static function (
                array $a,
                array $b
            ): int {
                return strcmp(
                    (string)$a['data'],
                    (string)$b['data']
                );
            }
        );

        return $dados;
    }

    // ==================================================
    // LISTAR REALIZADO
    // ==================================================

    public function listarRealizado(
        int $empresaId,
        array $filtros = []
    ): array {

        $this->validarEmpresa($empresaId);

        $filtros = $this->normalizarFiltros(
            $filtros
        );

        $dados = [];

        if (
            $filtros['origem'] === null
            || $filtros['origem'] === 'CAIXA'
        ) {
            $dados = array_merge(
                $dados,
                $this->listarMovimentosCaixa(
                    $empresaId,
                    $filtros
                )
            );
        }

        if (
            $filtros['origem'] === null
            || $filtros['origem'] === 'BANCO'
        ) {
            $dados = array_merge(
                $dados,
                $this->listarMovimentosBancarios(
                    $empresaId,
                    $filtros
                )
            );
        }

        usort(
            $dados,
            static function (
                array $a,
                array $b
            ): int {
                $comparacao = strcmp(
                    (string)$b['data'],
                    (string)$a['data']
                );

                if ($comparacao !== 0) {
                    return $comparacao;
                }

                return ((int)$b['id'])
                    <=> ((int)$a['id']);
            }
        );

        return $dados;
    }

    // ==================================================
    // SALDO NO INÍCIO DO PERÍODO
    // ==================================================

    private function saldoInicialPeriodo(
        int $empresaId,
        array $filtros
    ): float {

        /*
     * Se não existe data inicial, não é possível
     * reconstruir um saldo histórico.
     *
     * Nesse caso, utilizamos o saldo disponível atual.
     */

        if (empty($filtros['data_inicio'])) {

            $disponibilidades =
                $this->disponibilidades(
                    $empresaId,
                    $filtros
                );

            return (float)$disponibilidades['saldo_total_disponivel'];
        }

        /*
     * Saldo atual disponível.
     */

        $filtrosDisponibilidade = [
            'filial_id' =>
            $filtros['filial_id'] ?? null
        ];

        $disponibilidades =
            $this->disponibilidades(
                $empresaId,
                $filtrosDisponibilidade
            );

        $saldoAtual =
            (float)$disponibilidades['saldo_total_disponivel'];

        /*
     * Procuramos todos os movimentos realizados desde
     * a data inicial até hoje.
     *
     * Não aplicamos natureza nem origem, porque o saldo
     * atual contém todos os movimentos financeiros.
     */

        $filtrosMovimentos = [
            'tipo_fluxo' =>
            'REALIZADO',

            'data_inicio' =>
            $filtros['data_inicio'],

            'data_fim' =>
            null,

            'filial_id' =>
            $filtros['filial_id'] ?? null,

            'natureza' =>
            null,

            'origem' =>
            null
        ];

        $movimentos =
            $this->listarRealizado(
                $empresaId,
                $filtrosMovimentos
            );

        $entradasDesdeInicio = 0.0;
        $saidasDesdeInicio = 0.0;

        foreach ($movimentos as $movimento) {

            $valor = (float)(
                $movimento['valor'] ?? 0
            );

            $natureza = strtoupper(
                trim(
                    (string)(
                        $movimento['natureza'] ?? ''
                    )
                )
            );

            if ($natureza === 'ENTRADA') {

                $entradasDesdeInicio +=
                    $valor;
            } elseif ($natureza === 'SAIDA') {

                $saidasDesdeInicio +=
                    $valor;
            }
        }

        /*
     * Reconstrução do saldo anterior:
     *
     * saldo atual
     * - entradas ocorridas desde a data inicial
     * + saídas ocorridas desde a data inicial
     */

        return
            $saldoAtual
            - $entradasDesdeInicio
            + $saidasDesdeInicio;
    }

    // ==================================================
    // FLUXO CONSOLIDADO
    // ==================================================

    public function consolidado(
        int $empresaId,
        array $filtros = []
    ): array {

        $this->validarEmpresa(
            $empresaId
        );

        $filtros =
            $this->normalizarFiltros(
                $filtros
            );

        $dados = [];

        /*
     * Inclui valores previstos.
     */

        if (
            $filtros['tipo_fluxo'] === 'TODOS'
            || $filtros['tipo_fluxo'] === 'PREVISTO'
        ) {

            $dados = array_merge(
                $dados,
                $this->listarPrevisto(
                    $empresaId,
                    $filtros
                )
            );
        }

        /*
     * Inclui valores realizados.
     */

        if (
            $filtros['tipo_fluxo'] === 'TODOS'
            || $filtros['tipo_fluxo'] === 'REALIZADO'
        ) {

            $dados = array_merge(
                $dados,
                $this->listarRealizado(
                    $empresaId,
                    $filtros
                )
            );
        }

        /*
     * Ordenação cronológica.
     */

        usort(
            $dados,
            static function (
                array $a,
                array $b
            ): int {

                /*
        |--------------------------------------------------------------------------
        | 1. COMPARAR SOMENTE O DIA
        |--------------------------------------------------------------------------
        */

                $dataCompletaA =
                    (string)($a['data'] ?? '');

                $dataCompletaB =
                    (string)($b['data'] ?? '');


                $diaA =
                    substr(
                        $dataCompletaA,
                        0,
                        10
                    );

                $diaB =
                    substr(
                        $dataCompletaB,
                        0,
                        10
                    );


                $comparacaoDia =
                    strcmp(
                        $diaA,
                        $diaB
                    );


                if ($comparacaoDia !== 0) {
                    return $comparacaoDia;
                }


                /*
        |--------------------------------------------------------------------------
        | 2. NO MESMO DIA:
        |    REALIZADO PRIMEIRO
        |--------------------------------------------------------------------------
        */

                $situacaoA =
                    strtoupper(
                        trim(
                            (string)(
                                $a['situacao']
                                ?? ''
                            )
                        )
                    );

                $situacaoB =
                    strtoupper(
                        trim(
                            (string)(
                                $b['situacao']
                                ?? ''
                            )
                        )
                    );


                if ($situacaoA !== $situacaoB) {

                    if ($situacaoA === 'REALIZADO') {
                        return -1;
                    }

                    if ($situacaoB === 'REALIZADO') {
                        return 1;
                    }
                }


                /*
        |--------------------------------------------------------------------------
        | 3. REALIZADOS DO MESMO DIA:
        |    ORDEM CRONOLÓGICA
        |--------------------------------------------------------------------------
        */

                if (
                    $situacaoA === 'REALIZADO'
                    && $situacaoB === 'REALIZADO'
                ) {

                    $comparacaoHora =
                        strcmp(
                            $dataCompletaA,
                            $dataCompletaB
                        );


                    if ($comparacaoHora !== 0) {
                        return $comparacaoHora;
                    }
                }


                /*
        |--------------------------------------------------------------------------
        | 4. DESEMPATE
        |--------------------------------------------------------------------------
        */

                return
                    (int)($a['id'] ?? 0)
                    <=>
                    (int)($b['id'] ?? 0);
            }
        );

        /*
     * Agora o saldo começa no saldo real existente
     * antes do primeiro dia do período.
     */

        $saldoAcumulado =
            $this->saldoInicialPeriodo(
                $empresaId,
                $filtros
            );

        foreach ($dados as &$item) {

            $valor = (float)(
                $item['valor'] ?? 0
            );

            $natureza = strtoupper(
                trim(
                    (string)(
                        $item['natureza'] ?? ''
                    )
                )
            );

            $item['entrada'] = 0.0;
            $item['saida'] = 0.0;

            if ($natureza === 'ENTRADA') {

                $item['entrada'] =
                    $valor;

                $saldoAcumulado +=
                    $valor;
            } elseif ($natureza === 'SAIDA') {

                $item['saida'] =
                    $valor;

                $saldoAcumulado -=
                    $valor;
            }

            $item['saldo_acumulado'] =
                round(
                    $saldoAcumulado,
                    2
                );
        }

        unset($item);

        return $dados;
    }

    // ==================================================
    // GRÁFICO DO FLUXO DE CAIXA
    // ==================================================

    public function grafico(
        int $empresaId,
        array $filtros = [],
        ?float $saldoInicial = null
    ): array {

        $this->validarEmpresa(
            $empresaId
        );

        $filtros =
            $this->normalizarFiltros(
                $filtros
            );

        /*
     * Usa os mesmos movimentos do fluxo consolidado,
     * mas sem recalcular o saldo em separado.
     */

        $movimentos =
            $this->consolidadoSemSaldo(
                $empresaId,
                $filtros
            );

        if ($saldoInicial === null) {

            $saldoInicial =
                $this->saldoInicialPeriodo(
                    $empresaId,
                    $filtros
                );
        }

        /*
     * Agrupa os movimentos por dia.
     */

        $agrupado = [];

        foreach ($movimentos as $movimento) {

            if (empty($movimento['data'])) {
                continue;
            }

            $data = substr(
                (string)$movimento['data'],
                0,
                10
            );

            if (!isset($agrupado[$data])) {

                $agrupado[$data] = [
                    'entrada' => 0.0,
                    'saida' => 0.0
                ];
            }

            $valor = (float)(
                $movimento['valor'] ?? 0
            );

            $natureza = strtoupper(
                trim(
                    (string)(
                        $movimento['natureza'] ?? ''
                    )
                )
            );

            if ($natureza === 'ENTRADA') {

                $agrupado[$data]['entrada'] +=
                    $valor;
            } elseif ($natureza === 'SAIDA') {

                $agrupado[$data]['saida'] +=
                    $valor;
            }
        }

        ksort($agrupado);

        $saldo = $saldoInicial;

        $labels = [];
        $saldos = [];
        $entradas = [];
        $saidas = [];

        /*
     * Primeiro ponto do gráfico:
     * saldo existente no começo do período.
     */

        if (!empty($filtros['data_inicio'])) {

            $labels[] =
                $filtros['data_inicio'];

            $saldos[] =
                round(
                    $saldo,
                    2
                );

            $entradas[] =
                0.0;

            $saidas[] =
                0.0;
        }

        foreach (
            $agrupado as
            $data => $valores
        ) {

            $entrada =
                (float)$valores['entrada'];

            $saida =
                (float)$valores['saida'];

            $saldo +=
                $entrada - $saida;

            $labels[] =
                $data;

            $entradas[] =
                round(
                    $entrada,
                    2
                );

            $saidas[] =
                round(
                    $saida,
                    2
                );

            $saldos[] =
                round(
                    $saldo,
                    2
                );
        }

        return [
            'saldo_inicial' =>
            round(
                $saldoInicial,
                2
            ),

            'saldo_final' =>
            round(
                $saldo,
                2
            ),

            'labels' =>
            $labels,

            'saldos' =>
            $saldos,

            'entradas' =>
            $entradas,

            'saidas' =>
            $saidas
        ];
    }

    // ==================================================
    // MOVIMENTOS DE CAIXA
    // ==================================================

    private function listarMovimentosCaixa(
        int $empresaId,
        array $filtros
    ): array {

        $sql = "
        SELECT
            mc.id,

            mc.data_movimento AS data,

            CASE
                WHEN mc.tipo IN (
                    'ENTRADA',
                    'SUPRIMENTO'
                )
                THEN 'ENTRADA'

                WHEN mc.tipo IN (
                    'SAIDA',
                    'SANGRIA'
                )
                THEN 'SAIDA'

                ELSE 'NEUTRO'
            END AS natureza,

            'REALIZADO' AS situacao,

            'CAIXA' AS origem,

            mc.caixa_id AS origem_id,

            c.nome AS conta_origem,

            NULL AS entidade,

            COALESCE(
                NULLIF(mc.descricao, ''),
                'Movimento de caixa'
            ) AS descricao,

            CASE
                WHEN mc.documento_id IS NOT NULL
                THEN CONCAT(
                    'Documento #',
                    mc.documento_id
                )
                ELSE NULL
            END AS documento,

            mc.valor,

            COALESCE(
                f.nome,
                'Sem filial'
            ) AS filial,

            u.nome AS operador,

            mc.tipo AS categoria,

            NULL AS status

        FROM movimentos_caixa mc

        INNER JOIN caixas c
            ON c.id = mc.caixa_id

        LEFT JOIN filiais f
            ON f.id = mc.filial_id

        INNER JOIN usuarios u
            ON u.id = mc.usuario_id

        WHERE mc.empresa_id = :empresa_id

        AND mc.tipo <> 'TRANSFERENCIA'

        AND (
                mc.descricao IS NULL
                OR LOWER(mc.descricao)
                    NOT LIKE 'transferência%'
            )
            ";

        $params = [
            ':empresa_id' => $empresaId
        ];

        $this->aplicarFiltrosComuns(
            $sql,
            $params,
            $filtros,
            'mc'
        );

        if ($filtros['natureza'] === 'ENTRADA') {
            $sql .= "
            AND mc.tipo IN (
                'ENTRADA',
                'SUPRIMENTO'
            )
        ";
        }

        if ($filtros['natureza'] === 'SAIDA') {
            $sql .= "
            AND mc.tipo IN (
                'SAIDA',
                'SANGRIA'
            )
        ";
        }

        $sql .= "
        ORDER BY
            mc.data_movimento DESC,
            mc.id DESC
    ";

        $stmt = $this->pdo->prepare($sql);

        $stmt->execute($params);

        return $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );
    }
    // ==================================================
    // MOVIMENTOS BANCÁRIOS
    // ==================================================

    private function listarMovimentosBancarios(
        int $empresaId,
        array $filtros
    ): array {

        $sql = "
            SELECT
                mb.id,
                mb.data_movimento AS data,
                mb.tipo AS natureza,

                'REALIZADO' AS situacao,
                'BANCO' AS origem,

                mb.banco_id AS origem_id,

                CONCAT(
                    b.nome_banco,
                    ' - ',
                    b.conta
                ) AS conta_origem,

                NULL AS entidade,

                mb.descricao,
                mb.documento,
                mb.valor,

                COALESCE(
                    f.nome,
                    'Sem filial'
                ) AS filial,

                u.nome AS operador,

                mb.categoria

            FROM movimentos_bancarios mb

            INNER JOIN bancos b
                ON b.id = mb.banco_id

            LEFT JOIN filiais f
                ON f.id = mb.filial_id

            LEFT JOIN usuarios u
                ON u.id = mb.usuario_id

            WHERE mb.empresa_id = :empresa_id
        ";

        $params = [
            ':empresa_id' => $empresaId
        ];

        $this->aplicarFiltrosComuns(
            $sql,
            $params,
            $filtros,
            'mb'
        );

        if ($filtros['natureza'] !== null) {
            $sql .= "
                AND mb.tipo = :natureza
            ";

            $params[':natureza'] =
                $filtros['natureza'];
        }

        $stmt = $this->pdo->prepare(
            $sql
        );

        $stmt->execute(
            $params
        );

        return $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );
    }

    // ==================================================
    // CONTAS A RECEBER PREVISTAS
    // ==================================================

    // ==================================================
    // CONTAS A RECEBER PREVISTAS
    // ==================================================

    private function listarContasReceberPrevistas(
        int $empresaId,
        array $filtros
    ): array {

        /*
     * Conta a receber representa uma ENTRADA.
     *
     * Se o utilizador selecionar SAIDA,
     * não existem contas a receber para listar.
     */

        if ($filtros['natureza'] === 'SAIDA') {
            return [];
        }

        $sql = "
        SELECT
            cr.id,

            cr.data_vencimento AS data,

            'ENTRADA' AS natureza,

            'PREVISTO' AS situacao,

            'CONTA_RECEBER' AS origem,

            cr.id AS origem_id,

            NULL AS conta_origem,

            COALESCE(
                cli.nome,
                'Cliente não informado'
            ) AS entidade,

            COALESCE(
                NULLIF(cr.observacoes, ''),
                CONCAT(
                    'Conta a receber nº ',
                    cr.id
                )
            ) AS descricao,

            cr.numero_documento AS documento,

            cr.saldo AS valor,

            COALESCE(
                f.nome,
                'Sem filial'
            ) AS filial,

            NULL AS operador,

            s.nome AS categoria,

            s.nome AS status

        FROM contas_receber cr

        INNER JOIN clientes cli
            ON cli.id = cr.cliente_id

        LEFT JOIN filiais f
            ON f.id = cr.filial_id

        INNER JOIN status s
            ON s.id = cr.status_id

        WHERE cr.empresa_id = :empresa_id

          AND cr.saldo > 0

          AND LOWER(s.nome) NOT IN (
              'pago',
              'recebido',
              'cancelado'
          )
    ";

        $params = [
            ':empresa_id' => $empresaId
        ];

        /*
     * FILIAL
     *
     * Sem filial selecionada:
     *   mostra contas com filial e sem filial.
     *
     * Com filial selecionada:
     *   mostra somente contas dessa filial.
     */

        if ($filtros['filial_id'] !== null) {

            $sql .= "
            AND cr.filial_id = :filial_id
        ";

            $params[':filial_id'] =
                $filtros['filial_id'];
        }

        /*
     * DATA INICIAL
     */

        if ($filtros['data_inicio'] !== null) {

            $sql .= "
            AND cr.data_vencimento >= :data_inicio
        ";

            $params[':data_inicio'] =
                $filtros['data_inicio'];
        }

        /*
     * DATA FINAL
     */

        if ($filtros['data_fim'] !== null) {

            $sql .= "
            AND cr.data_vencimento <= :data_fim
        ";

            $params[':data_fim'] =
                $filtros['data_fim'];
        }

        $sql .= "
        ORDER BY
            cr.data_vencimento ASC,
            cr.id ASC
    ";

        $stmt = $this->pdo->prepare($sql);

        $stmt->execute($params);

        return $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );
    }

    // ==================================================
    // CONTAS A PAGAR PREVISTAS
    // ==================================================

    private function listarContasPagarPrevistas(
        int $empresaId,
        array $filtros
    ): array {

        if ($filtros['natureza'] === 'ENTRADA') {
            return [];
        }

        $sql = "
        SELECT
            cp.id,

            cp.data_vencimento AS data,

            'SAIDA' AS natureza,

            'PREVISTO' AS situacao,

            'CONTA_PAGAR' AS origem,

            cp.id AS origem_id,

            NULL AS conta_origem,

            COALESCE(
                forn.nome,
                'Fornecedor não informado'
            ) AS entidade,

            cp.descricao,

            cp.numero_documento AS documento,

            cp.saldo AS valor,

            COALESCE(
                f.nome,
                'Sem filial'
            ) AS filial,

            NULL AS operador,

            s.nome AS categoria,

            s.nome AS status

        FROM contas_pagar cp

        INNER JOIN fornecedores forn
            ON forn.id = cp.fornecedor_id

        LEFT JOIN filiais f
            ON f.id = cp.filial_id

        INNER JOIN status s
            ON s.id = cp.status_id

        WHERE cp.empresa_id = :empresa_id

          AND cp.saldo > 0

          AND LOWER(s.nome) NOT IN (
              'pago',
              'cancelado'
          )
    ";

        $params = [
            ':empresa_id' => $empresaId
        ];

        if ($filtros['filial_id'] !== null) {
            $sql .= "
            AND cp.filial_id = :filial_id
        ";

            $params[':filial_id'] =
                $filtros['filial_id'];
        }

        if ($filtros['data_inicio'] !== null) {
            $sql .= "
            AND cp.data_vencimento >= :data_inicio
        ";

            $params[':data_inicio'] =
                $filtros['data_inicio'];
        }

        if ($filtros['data_fim'] !== null) {
            $sql .= "
            AND cp.data_vencimento <= :data_fim
        ";

            $params[':data_fim'] =
                $filtros['data_fim'];
        }

        $sql .= "
        ORDER BY
            cp.data_vencimento ASC,
            cp.id ASC
    ";

        $stmt = $this->pdo->prepare($sql);

        $stmt->execute($params);

        return $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );
    }

    // ==================================================
    // TOTAL CONTAS A RECEBER
    // ==================================================

    // ==================================================
    // TOTAL CONTAS A RECEBER
    // ==================================================

    private function totalContasReceber(
        int $empresaId,
        array $filtros,
        bool $apenasVencidas
    ): float {

        $sql = "
        SELECT
            COALESCE(
                SUM(cr.saldo),
                0
            )

        FROM contas_receber cr

        INNER JOIN status s
            ON s.id = cr.status_id

        WHERE cr.empresa_id = :empresa_id

          AND cr.saldo > 0

          AND LOWER(s.nome) NOT IN (
              'pago',
              'recebido',
              'cancelado'
          )
    ";

        $params = [
            ':empresa_id' => $empresaId
        ];

        /*
     * FILIAL
     *
     * Sem filial selecionada:
     *   soma todas as contas da empresa,
     *   tenham elas filial ou não.
     *
     * Com filial selecionada:
     *   soma somente as contas daquela filial.
     */

        if ($filtros['filial_id'] !== null) {

            $sql .= "
            AND cr.filial_id = :filial_id
        ";

            $params[':filial_id'] =
                $filtros['filial_id'];
        }

        /*
     * DATA INICIAL
     */

        if ($filtros['data_inicio'] !== null) {

            $sql .= "
            AND cr.data_vencimento >= :data_inicio
        ";

            $params[':data_inicio'] =
                $filtros['data_inicio'];
        }

        /*
     * DATA FINAL
     */

        if ($filtros['data_fim'] !== null) {

            $sql .= "
            AND cr.data_vencimento <= :data_fim
        ";

            $params[':data_fim'] =
                $filtros['data_fim'];
        }

        /*
     * SOMENTE VENCIDAS
     */

        if ($apenasVencidas) {

            $sql .= "
            AND cr.data_vencimento < CURDATE()
        ";
        }

        $stmt = $this->pdo->prepare($sql);

        $stmt->execute($params);

        return (float)$stmt->fetchColumn();
    }

    // ==================================================
    // TOTAL CONTAS A PAGAR
    // ==================================================

    private function totalContasPagar(
        int $empresaId,
        array $filtros,
        bool $apenasVencidas
    ): float {

        $sql = "
        SELECT
            COALESCE(
                SUM(cp.saldo),
                0
            )

        FROM contas_pagar cp

        INNER JOIN status s
            ON s.id = cp.status_id

        WHERE cp.empresa_id = :empresa_id

          AND cp.saldo > 0

          AND LOWER(s.nome) NOT IN (
              'pago',
              'cancelado'
          )
    ";

        $params = [
            ':empresa_id' => $empresaId
        ];

        if ($filtros['filial_id'] !== null) {
            $sql .= "
            AND cp.filial_id = :filial_id
        ";

            $params[':filial_id'] =
                $filtros['filial_id'];
        }

        if ($filtros['data_inicio'] !== null) {
            $sql .= "
            AND cp.data_vencimento >= :data_inicio
        ";

            $params[':data_inicio'] =
                $filtros['data_inicio'];
        }

        if ($filtros['data_fim'] !== null) {
            $sql .= "
            AND cp.data_vencimento <= :data_fim
        ";

            $params[':data_fim'] =
                $filtros['data_fim'];
        }

        if ($apenasVencidas) {
            $sql .= "
            AND cp.data_vencimento < CURDATE()
        ";
        }

        $stmt = $this->pdo->prepare($sql);

        $stmt->execute($params);

        return (float)$stmt->fetchColumn();
    }
    // ==================================================
    // CONSOLIDADO SEM SALDO
    // ==================================================
    private function consolidadoSemSaldo(
        int $empresaId,
        array $filtros
    ): array {

        $dados = [];

        if (
            $filtros['tipo_fluxo'] === 'TODOS'
            || $filtros['tipo_fluxo'] === 'PREVISTO'
        ) {

            $dados = array_merge(
                $dados,
                $this->listarPrevisto(
                    $empresaId,
                    $filtros
                )
            );
        }

        if (
            $filtros['tipo_fluxo'] === 'TODOS'
            || $filtros['tipo_fluxo'] === 'REALIZADO'
        ) {

            $dados = array_merge(
                $dados,
                $this->listarRealizado(
                    $empresaId,
                    $filtros
                )
            );
        }

        usort(
            $dados,
            static function (
                array $a,
                array $b
            ): int {

                $comparacao = strcmp(
                    (string)($a['data'] ?? ''),
                    (string)($b['data'] ?? '')
                );

                if ($comparacao !== 0) {
                    return $comparacao;
                }

                return
                    (int)($a['id'] ?? 0)
                    <=>
                    (int)($b['id'] ?? 0);
            }
        );

        return $dados;
    }

    // ==================================================
    // FILTROS COMUNS DE MOVIMENTOS
    // ==================================================

    private function aplicarFiltrosComuns(
        string &$sql,
        array &$params,
        array $filtros,
        string $alias
    ): void {

        if ($filtros['filial_id'] !== null) {
            $sql .= "
                AND {$alias}.filial_id
                    = :filial_id
            ";

            $params[':filial_id'] =
                $filtros['filial_id'];
        }

        if ($filtros['data_inicio'] !== null) {
            $sql .= "
                AND DATE(
                    {$alias}.data_movimento
                ) >= :data_inicio
            ";

            $params[':data_inicio'] =
                $filtros['data_inicio'];
        }

        if ($filtros['data_fim'] !== null) {
            $sql .= "
                AND DATE(
                    {$alias}.data_movimento
                ) <= :data_fim
            ";

            $params[':data_fim'] =
                $filtros['data_fim'];
        }
    }

    // ==================================================
    // FILTROS DE VENCIMENTO
    // ==================================================

    private function aplicarFiltrosVencimento(
        string &$sql,
        array &$params,
        array $filtros,
        string $alias
    ): void {

        if ($filtros['filial_id'] !== null) {
            $sql .= "
                AND {$alias}.filial_id
                    = :filial_id
            ";

            $params[':filial_id'] =
                $filtros['filial_id'];
        }

        if ($filtros['data_inicio'] !== null) {
            $sql .= "
                AND DATE(
                    {$alias}.data_vencimento
                ) >= :data_inicio
            ";

            $params[':data_inicio'] =
                $filtros['data_inicio'];
        }

        if ($filtros['data_fim'] !== null) {
            $sql .= "
                AND DATE(
                    {$alias}.data_vencimento
                ) <= :data_fim
            ";

            $params[':data_fim'] =
                $filtros['data_fim'];
        }
    }

    // ==================================================
    // NORMALIZAR FILTROS
    // ==================================================

    private function normalizarFiltros(
        array $filtros
    ): array {

        $tipoFluxo = strtoupper(
            trim((string)(
                $filtros['tipo_fluxo']
                ?? $filtros['tipo']
                ?? 'TODOS'
            ))
        );

        if (
            !in_array(
                $tipoFluxo,
                [
                    'TODOS',
                    'PREVISTO',
                    'REALIZADO'
                ],
                true
            )
        ) {
            $tipoFluxo = 'TODOS';
        }

        $natureza = strtoupper(
            trim((string)(
                $filtros['natureza']
                ?? ''
            ))
        );

        if (
            !in_array(
                $natureza,
                [
                    'ENTRADA',
                    'SAIDA'
                ],
                true
            )
        ) {
            $natureza = null;
        }

        $origem = strtoupper(
            trim((string)(
                $filtros['origem']
                ?? ''
            ))
        );

        if (
            !in_array(
                $origem,
                [
                    'CONTA_RECEBER',
                    'CONTA_PAGAR',
                    'CAIXA',
                    'BANCO'
                ],
                true
            )
        ) {
            $origem = null;
        }

        $dataInicio =
            $this->normalizarData(
                $filtros['data_inicio']
                    ?? null
            );

        $dataFim =
            $this->normalizarData(
                $filtros['data_fim']
                    ?? null
            );

        if (
            $dataInicio !== null
            && $dataFim !== null
            && $dataInicio > $dataFim
        ) {
            throw new RuntimeException(
                'A data inicial não pode ser superior à data final.'
            );
        }

        return [
            'tipo_fluxo' =>
            $tipoFluxo,

            'natureza' =>
            $natureza,

            'origem' =>
            $origem,

            'filial_id' =>
            !empty($filtros['filial_id'])
                ? (int)$filtros['filial_id']
                : null,

            'data_inicio' =>
            $dataInicio,

            'data_fim' =>
            $dataFim
        ];
    }

    // ==================================================
    // NORMALIZAR DATA
    // ==================================================

    private function normalizarData(
        mixed $data
    ): ?string {

        if (
            $data === null
            || trim((string)$data) === ''
        ) {
            return null;
        }

        $valor = trim(
            (string)$data
        );

        $objeto = DateTime::createFromFormat(
            'Y-m-d',
            $valor
        );

        if (
            !$objeto
            || $objeto->format('Y-m-d')
            !== $valor
        ) {
            throw new RuntimeException(
                'Data inválida: ' . $valor
            );
        }

        return $valor;
    }

    // ==================================================
    // VALIDAR EMPRESA
    // ==================================================

    private function validarEmpresa(
        int $empresaId
    ): void {

        if ($empresaId <= 0) {
            throw new RuntimeException(
                'Empresa inválida para consultar o fluxo de caixa.'
            );
        }
    }
}
