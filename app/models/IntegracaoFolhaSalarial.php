<?php

declare(strict_types=1);

require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/MovimentoBancario.php';

final class IntegracaoFolhaSalarial
{
    private PDO $db;
    private bool $transacaoPropria = false;
    private ?int $centroCustoFolhaAtual = null;

    public function __construct()
    {
        $this->db = Database::conn();
    }

    /**
     * Inicia transação
     */
    private function iniciarTransacao(): void
    {
        if (!$this->db->inTransaction()) {

            $this->db->beginTransaction();

            $this->transacaoPropria = true;

            return;
        }

        $this->transacaoPropria = false;
    }

    private function confirmarTransacao(): void
    {
        if (
            $this->transacaoPropria
            && $this->db->inTransaction()
        ) {
            $this->db->commit();
        }

        $this->transacaoPropria = false;
    }

    private function cancelarTransacao(): void
    {
        if (
            $this->transacaoPropria
            && $this->db->inTransaction()
        ) {
            $this->db->rollBack();
        }

        $this->transacaoPropria = false;
    }

    /**
     * UUID
     */
    private function gerarUuid(): string
    {
        $dados = random_bytes(16);

        $dados[6] = chr((ord($dados[6]) & 0x0f) | 0x40);
        $dados[8] = chr((ord($dados[8]) & 0x3f) | 0x80);

        return vsprintf(
            '%s%s-%s-%s-%s-%s%s%s',
            str_split(bin2hex($dados), 4)
        );
    }

    /**
     * Obtém configuração contabilística
     */
    private function obterConfiguracao(
        int $empresaId
    ): array {

        $sql = "
            SELECT *
            FROM configuracoes_contabilisticas
            WHERE empresa_id = ?
            LIMIT 1
        ";

        $stm = $this->db->prepare($sql);

        $stm->execute([$empresaId]);

        $config = $stm->fetch(PDO::FETCH_ASSOC);

        if (!$config) {

            throw new Exception(
                'Configuração contabilística não encontrada.'
            );
        }

        return $config;
    }

    private function obterConfiguracaoRh(
        int $empresaId
    ): array {

        $sql = "
        SELECT
            cfg.*

        FROM configuracoes_contabilisticas_rh cfg

        WHERE cfg.empresa_id =
            :empresa_id

        LIMIT 1
    ";

        $stmt =
            $this->db->prepare($sql);

        $stmt->execute([
            ':empresa_id' =>
            $empresaId
        ]);

        $config =
            $stmt->fetch(
                PDO::FETCH_ASSOC
            );

        if (!$config) {
            throw new RuntimeException(
                'A configuração contabilística do RH não foi encontrada.'
            );
        }

        return $config;
    }


    /**
     * Próximo número do diário
     */
    private function proximoNumeroDiario(
        int $diarioId
    ): int {

        $sql = "
            UPDATE diarios_contabilisticos
            SET ultimo_numero = ultimo_numero + 1
            WHERE id = ?
        ";

        $stm = $this->db->prepare($sql);

        $stm->execute([$diarioId]);

        $sql = "
            SELECT ultimo_numero
            FROM diarios_contabilisticos
            WHERE id = ?
        ";

        $stm = $this->db->prepare($sql);

        $stm->execute([$diarioId]);

        return (int)$stm->fetchColumn();
    }


    /**
     * Contabiliza uma folha salarial.
     */
    public function contabilizarFolha(
        int $folhaId,
        int $empresaId,
        ?int $usuarioId
    ): array {

        if ($folhaId <= 0) {
            throw new InvalidArgumentException(
                'O ID da folha salarial é obrigatório.'
            );
        }

        if ($empresaId <= 0) {
            throw new InvalidArgumentException(
                'A empresa não foi identificada.'
            );
        }

        try {

            $this->iniciarTransacao();


            /*
        |--------------------------------------------------------------------------
        | Folha
        |--------------------------------------------------------------------------
        */

            $folha =
                $this->obterFolhaEmpresa(
                    $folhaId,
                    $empresaId
                );


            /*
        |--------------------------------------------------------------------------
        | Duplicação
        |--------------------------------------------------------------------------
        */

            if (
                $this->folhaJaIntegradaEmpresa(
                    $folhaId,
                    $empresaId
                )
            ) {
                throw new RuntimeException(
                    'Esta folha salarial já possui integração contabilística.'
                );
            }


            /*
        |--------------------------------------------------------------------------
        | Configuração RH
        |--------------------------------------------------------------------------
        */

            $config =
                $this->obterConfiguracaoRh(
                    $empresaId
                );

            /*
            |------------------------------------------------------------------
            | Filial efetiva da folha
            |------------------------------------------------------------------
            |
            | A filial é obtida dos trabalhadores processados. Não usamos a
            | filial da sessão, porque o utilizador pode processar uma unidade
            | diferente daquela em que iniciou sessão.
            */
            $filialId =
                $this->resolverFilialFolha(
                    (int)$folha['periodo_id'],
                    $empresaId
                );


            /*
        |--------------------------------------------------------------------------
        | Diário
        |--------------------------------------------------------------------------
        */

            $diario =
                $this->obterDiarioFolhaRh(
                    $empresaId,
                    $config
                );


            /*
        |--------------------------------------------------------------------------
        | Período contabilístico
        |--------------------------------------------------------------------------
        */

            $periodoContabilistico =
                $this
                ->obterPeriodoContabilisticoFolhaRh(
                    $folha,
                    $empresaId
                );

            $exercicioId =
                (int)(
                    $periodoContabilistico['exercicio_id']
                    ?? 0
                );

            if ($exercicioId <= 0) {
                throw new RuntimeException(
                    'O exercício contabilístico da folha não foi identificado.'
                );
            }


            /*
        |--------------------------------------------------------------------------
        | Contas RH
        |--------------------------------------------------------------------------
        */

            $contas =
                $this->obterContasFolhaRh(
                    $empresaId,
                    $config
                );


            /*
        |--------------------------------------------------------------------------
        | Rubricas
        |--------------------------------------------------------------------------
        */

            $rubricas =
                $this
                ->obterRubricasContabilisticasFolha(
                    (int)$folha['periodo_id'],
                    $empresaId
                );


            /*
        |--------------------------------------------------------------------------
        | Número do diário
        |--------------------------------------------------------------------------
        */

            $numero =
                $this->proximoNumeroDiario(
                    (int)$diario['id']
                );


            /*
        |--------------------------------------------------------------------------
        | Primeiro calcular totais esperados
        |--------------------------------------------------------------------------
        */

            $totalEsperado =
                round(
                    (float)$folha['total_salario_base'],
                    2
                );

            foreach ($rubricas as $rubrica) {

                $valor =
                    round(
                        abs(
                            (float)$rubrica['valor_total']
                        ),
                        2
                    );

                $totalEsperado += $valor;
            }


            /*
        |--------------------------------------------------------------------------
        | Cabeçalho provisório
        |--------------------------------------------------------------------------
        */

            $totaisCabecalho = [
                'total_debito' =>
                $totalEsperado,

                'total_credito' =>
                $totalEsperado
            ];


            $lancamentoId =
                $this->criarLancamentoFolha(
                    $folha,
                    $empresaId,
                    $exercicioId,
                    (int)$periodoContabilistico['id'],
                    (int)$diario['id'],
                    $numero,
                    $totaisCabecalho,
                    $filialId,
                    $usuarioId
                );


            /*
        |--------------------------------------------------------------------------
        | Criar itens reais
        |--------------------------------------------------------------------------
        */

            $totais =
                $this->criarItensLancamentoFolhaRh(
                    $lancamentoId,
                    $folha,
                    $contas,
                    $rubricas,
                    $empresaId,
                    $config
                );


            /*
        |--------------------------------------------------------------------------
        | Atualizar totais reais do cabeçalho
        |--------------------------------------------------------------------------
        */

            $stmt =
                $this->db->prepare("
                UPDATE lancamentos_contabilisticos

                SET
                    total_debito =
                        :total_debito,

                    total_credito =
                        :total_credito

                WHERE id =
                    :lancamento_id

                  AND empresa_id =
                    :empresa_id
            ");

            $stmt->execute([
                ':total_debito' =>
                $totais['total_debito'],

                ':total_credito' =>
                $totais['total_credito'],

                ':lancamento_id' =>
                $lancamentoId,

                ':empresa_id' =>
                $empresaId
            ]);


            /*
        |--------------------------------------------------------------------------
        | Dados para integração
        |--------------------------------------------------------------------------
        */

            $totaisIntegracao =
                $this->prepararTotaisFolha(
                    $folha
                );

            $totaisIntegracao['total_debito'] =
                $totais['total_debito'];

            $totaisIntegracao['total_credito'] =
                $totais['total_credito'];


            $integracaoId =
                $this->criarIntegracaoFolha(
                    $folha,
                    $lancamentoId,
                    $totaisIntegracao,
                    $usuarioId
                );


            $this->confirmarTransacao();


            return [
                'id' =>
                $integracaoId,

                'folha_salarial_id' =>
                $folhaId,

                'lancamento_contabilistico_id' =>
                $lancamentoId,

                'numero_lancamento' =>
                $numero,

                'diario' =>
                $diario['codigo'],

                'total_debito' =>
                $totais['total_debito'],

                'total_credito' =>
                $totais['total_credito'],

                'mensagem' =>
                'Folha salarial contabilizada com sucesso.'
            ];
        } catch (Throwable $erro) {

            $this->cancelarTransacao();

            throw $erro;
        }
    }

    /**
     * Obtém uma folha salarial pertencente à empresa.
     */
    private function obterFolhaEmpresa(
        int $folhaId,
        int $empresaId
    ): array {

        $sql = "
        SELECT
            fs.*,

            ps.codigo AS periodo_codigo,
            ps.descricao AS periodo_nome,
            ps.ano AS periodo_ano,
            ps.mes AS periodo_mes,

            ps.data_inicio AS periodo_data_inicio,
            ps.data_fim AS periodo_data_fim

        FROM folhas_salariais fs

        INNER JOIN periodos_salariais ps
            ON ps.id = fs.periodo_id
           AND ps.empresa_id = fs.empresa_id

        WHERE fs.id = :folha_id
          AND fs.empresa_id = :empresa_id

        LIMIT 1
    ";

        $stmt =
            $this->db->prepare($sql);

        $stmt->execute([
            ':folha_id' => $folhaId,
            ':empresa_id' => $empresaId
        ]);

        $folha =
            $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$folha) {
            throw new RuntimeException(
                'Folha salarial não encontrada para esta empresa.'
            );
        }

        return $folha;
    }

    /**
     * Resolve a filial a partir dos funcionários que compõem a folha.
     * Uma integração consolidada nunca pode receber uma filial arbitrária.
     */
    private function resolverFilialFolha(
        int $periodoId,
        int $empresaId
    ): ?int {
        $stmt = $this->db->prepare("
            SELECT DISTINCT f.filial_id
            FROM processamentos_salariais ps
            INNER JOIN funcionarios f
                ON f.id = ps.funcionario_id
               AND f.empresa_id = ps.empresa_id
            WHERE ps.empresa_id = :empresa_id
              AND ps.periodo_id = :periodo_id
            ORDER BY f.filial_id
        ");

        $stmt->execute([
            ':empresa_id' => $empresaId,
            ':periodo_id' => $periodoId
        ]);

        $filiais = $stmt->fetchAll(PDO::FETCH_COLUMN);

        if ($filiais === []) {
            throw new RuntimeException(
                'Não foi possível identificar os funcionários da folha salarial.'
            );
        }

        $filiaisValidas = [];
        $existeFuncionarioSemFilial = false;

        foreach ($filiais as $filial) {
            if ($filial === null || (int)$filial <= 0) {
                $existeFuncionarioSemFilial = true;
                continue;
            }

            $filiaisValidas[(int)$filial] = (int)$filial;
        }

        if (
            $existeFuncionarioSemFilial
            && $filiaisValidas === []
            && !$this->empresaPossuiFiliais($empresaId)
        ) {
            // Empresa sem estrutura de filiais: NULL é legítimo.
            return null;
        }

        if ($existeFuncionarioSemFilial) {
            throw new RuntimeException(
                'Existem funcionários da folha sem filial configurada. Defina a filial no cadastro dos funcionários antes de contabilizar.'
            );
        }

        if (count($filiaisValidas) !== 1) {
            throw new RuntimeException(
                'A folha reúne funcionários de várias filiais. Gere e contabilize a folha separadamente por filial.'
            );
        }

        return (int)array_key_first($filiaisValidas);
    }

    private function empresaPossuiFiliais(int $empresaId): bool
    {
        $stmt = $this->db->prepare("
            SELECT 1
            FROM filiais
            WHERE empresa_id = :empresa_id
            LIMIT 1
        ");
        $stmt->execute([
            ':empresa_id' => $empresaId
        ]);

        return (bool)$stmt->fetchColumn();
    }

    private function obterRubricasContabilisticasFolha(
        int $periodoId,
        int $empresaId
    ): array {

        $sql = "
        SELECT
            r.id AS rubrica_id,
            r.codigo,
            r.nome,
            r.tipo,
            r.categoria,

            SUM(psi.valor_total) AS valor_total,

            rc.id AS mapeamento_id,
            rc.conta_debito_id,
            rc.conta_credito_id,
            rc.usar_conta_salarios_pagar,
            rc.centro_custo_obrigatorio,
            rc.status_id AS mapeamento_status_id,

            st.nome AS mapeamento_estado

        FROM processamento_salario_itens psi

        INNER JOIN processamentos_salariais ps
            ON ps.id = psi.processamento_id
           AND ps.empresa_id = :empresa_id

        INNER JOIN rubricas_salariais r
            ON r.empresa_id = ps.empresa_id
           AND r.codigo = psi.codigo_rubrica

        LEFT JOIN rubricas_salariais_contas rc
            ON rc.empresa_id = r.empresa_id
           AND rc.rubrica_salarial_id = r.id

        LEFT JOIN status st
            ON st.id = rc.status_id

        WHERE ps.periodo_id = :periodo_id
            AND UPPER(r.categoria) <> 'SALARIO_BASE'

        GROUP BY
            r.id,
            r.codigo,
            r.nome,
            r.tipo,
            r.categoria,
            rc.id,
            rc.conta_debito_id,
            rc.conta_credito_id,
            rc.usar_conta_salarios_pagar,
            rc.centro_custo_obrigatorio,
            rc.status_id,
            st.nome

        HAVING ABS(SUM(psi.valor_total)) > 0.001

        ORDER BY
            r.tipo,
            r.nome
    ";

        $stmt =
            $this->db->prepare($sql);

        $stmt->execute([
            ':empresa_id' => $empresaId,
            ':periodo_id' => $periodoId
        ]);

        return $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );
    }

    private function obterContasFolhaRh(
        int $empresaId,
        array $config
    ): array {

        $camposObrigatorios = [
            'SALARIO_BASE' =>
            'conta_salario_base_id',

            'SALARIOS_PAGAR' =>
            'conta_salarios_pagar_id'
        ];

        $camposOpcionais = [
            'INSS_PAGAR' =>
            'conta_inss_pagar_id',

            'IRPS_PAGAR' =>
            'conta_irps_pagar_id',

            'OUTROS_DESCONTOS' =>
            'conta_outros_descontos_id',

            'ENCARGOS_PATRONAIS' =>
            'conta_encargos_patronais_id'
        ];

        $contas = [];

        foreach (
            $camposObrigatorios as
            $chave => $campo
        ) {
            $id =
                (int)($config[$campo] ?? 0);

            if ($id <= 0) {
                throw new RuntimeException(
                    "A configuração {$campo} não foi definida no RH."
                );
            }

            $contas[$chave] =
                $this->obterContaRhPorId(
                    $id,
                    $empresaId
                );
        }

        foreach (
            $camposOpcionais as
            $chave => $campo
        ) {
            $id =
                (int)($config[$campo] ?? 0);

            $contas[$chave] =
                $id > 0
                ? $this->obterContaRhPorId(
                    $id,
                    $empresaId
                )
                : null;
        }

        return $contas;
    }

    private function obterDiarioFolhaRh(
        int $empresaId,
        array $config
    ): array {

        $diarioId =
            (int)(
                $config['diario_contabilistico_id']
                ?? 0
            );

        if ($diarioId <= 0) {
            throw new RuntimeException(
                'O diário contabilístico do RH não está configurado.'
            );
        }

        $sql = "
        SELECT
            dc.*

        FROM diarios_contabilisticos dc

        WHERE dc.id = :diario_id
          AND dc.empresa_id = :empresa_id

        LIMIT 1
    ";

        $stmt =
            $this->db->prepare($sql);

        $stmt->execute([
            ':diario_id' => $diarioId,
            ':empresa_id' => $empresaId
        ]);

        $diario =
            $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$diario) {
            throw new RuntimeException(
                'O diário contabilístico configurado no RH não foi encontrado.'
            );
        }

        return $diario;
    }


    private function obterPeriodoContabilisticoFolhaRh(
        array $folha,
        int $empresaId
    ): array {

        $data =
            substr(
                trim(
                    (string)(
                        $folha['periodo_data_fim']
                        ?? $folha['periodo_data_inicio']
                        ?? ''
                    )
                ),
                0,
                10
            );

        if ($data === '') {
            throw new RuntimeException(
                'A folha não possui uma data válida para contabilização.'
            );
        }

        $sql = "
        SELECT
            pc.*

        FROM periodos_contabilisticos pc

        WHERE pc.empresa_id =
            :empresa_id

          AND :data_referencia
              BETWEEN pc.data_inicio
                  AND pc.data_fim

        ORDER BY
            pc.data_inicio DESC,
            pc.id DESC

        LIMIT 1
    ";

        $stmt =
            $this->db->prepare($sql);

        $stmt->execute([
            ':empresa_id' => $empresaId,
            ':data_referencia' => $data
        ]);

        $periodo =
            $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$periodo) {
            throw new RuntimeException(
                'Não existe período contabilístico para '
                    . $data
                    . '.'
            );
        }

        $estado = '';

        if (!empty($periodo['status_id'])) {
            $estado =
                $this->obterCodigoStatus(
                    (int)$periodo['status_id']
                );
        }

        if (
            in_array(
                $estado,
                [
                    'FECHADO',
                    'ENCERRADO',
                    'BLOQUEADO',
                    'CANCELADO'
                ],
                true
            )
        ) {
            throw new RuntimeException(
                'O período contabilístico da folha está fechado.'
            );
        }

        return $periodo;
    }

    /**
     * Verifica se a folha já foi integrada.
     */
    private function folhaJaIntegradaEmpresa(
        int $folhaId,
        int $empresaId
    ): bool {
        $sql = "
        SELECT COUNT(*)
        FROM integracoes_contabilisticas_folha
        WHERE empresa_id = :empresa_id
          AND folha_salarial_id = :folha_id
    ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':empresa_id' => $empresaId,
            ':folha_id' => $folhaId,
        ]);

        return (int)$stmt->fetchColumn() > 0;
    }

    /**
     * Obtém o diário de salários configurado.
     */
    private function obterDiarioConfigurado(
        int $empresaId,
        array $configuracao
    ): array {
        $diarioId = isset(
            $configuracao['diario_salarios_id']
        )
            ? (int)$configuracao['diario_salarios_id']
            : 0;

        if ($diarioId <= 0) {
            throw new RuntimeException(
                'O diário de salários não está configurado.'
            );
        }

        $sql = "
        SELECT
            dc.*
        FROM diarios_contabilisticos dc
        WHERE dc.id = :diario_id
          AND dc.empresa_id = :empresa_id
          AND dc.tipo = 'SALARIOS'
        LIMIT 1
    ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':diario_id' => $diarioId,
            ':empresa_id' => $empresaId,
        ]);

        $diario = $stmt->fetch(
            PDO::FETCH_ASSOC
        );

        if (!$diario) {
            throw new RuntimeException(
                'O diário de salários configurado não foi encontrado.'
            );
        }

        return $diario;
    }

    /**
     * Obtém as contas usadas na contabilização da folha.
     */
    private function obterContasFolha(
        int $empresaId,
        int $planoContasId
    ): array {
        $codigos = [
            'SALARIO_BASE',
            'ENCARGOS_PATRONAIS',
            'SALARIOS_PAGAR',
            'INSS_PAGAR',
            'IRPS_PAGAR',
            'OUTROS_DESCONTOS_PAGAR',
        ];

        $placeholders = implode(
            ',',
            array_fill(
                0,
                count($codigos),
                '?'
            )
        );

        $sql = "
        SELECT
            id,
            codigo,
            nome,
            integracao_codigo,
            tipo,
            aceita_lancamento
        FROM contas_contabilisticas
        WHERE empresa_id = ?
          AND plano_contas_id = ?
          AND integracao_codigo IN (
              {$placeholders}
          )
    ";

        $parametros = [
            $empresaId,
            $planoContasId,
            ...$codigos,
        ];

        $stmt = $this->db->prepare($sql);
        $stmt->execute($parametros);

        $resultado = $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );

        $contas = [];

        foreach ($resultado as $conta) {
            $integracao = (string)
            $conta['integracao_codigo'];

            if (
                $conta['tipo'] !== 'ANALITICA' ||
                (int)$conta['aceita_lancamento'] !== 1
            ) {
                throw new RuntimeException(
                    "A conta {$integracao} não permite lançamentos."
                );
            }

            $contas[$integracao] = $conta;
        }

        foreach ($codigos as $codigo) {
            if (!isset($contas[$codigo])) {
                throw new RuntimeException(
                    "A conta de integração {$codigo} não foi encontrada."
                );
            }
        }

        return $contas;
    }

    /**
     * Prepara os totais contabilísticos da folha.
     */
    private function prepararTotaisFolha(
        array $folha
    ): array {
        $totalBruto = round(
            (float)$folha['total_bruto'],
            2
        );

        $totalLiquido = round(
            (float)$folha['total_liquido'],
            2
        );

        $totalInss = round(
            (float)$folha['total_inss'],
            2
        );

        $totalInssPatronal = round(
            (float)(
                $folha['total_inss_patronal']
                ?? 0
            ),
            2
        );

        $totalIrps = round(
            (float)$folha['total_irps'],
            2
        );

        $totalOutrosDescontos = round(
            (float)$folha['total_outros_descontos'],
            2
        );

        $totalDebito = round(
            $totalBruto +
                $totalInssPatronal,
            2
        );

        $totalCredito = round(
            $totalLiquido +
                $totalInss +
                $totalInssPatronal +
                $totalIrps +
                $totalOutrosDescontos,
            2
        );

        return [
            'total_salario_base' => round(
                (float)$folha['total_salario_base'],
                2
            ),

            'total_subsidios' => round(
                (float)$folha['total_subsidios'],
                2
            ),

            'total_horas_extras' => round(
                (float)$folha['total_horas_extras'],
                2
            ),

            'total_outros_proventos' => round(
                (float)$folha['total_outros_proventos'],
                2
            ),

            'total_bruto' => $totalBruto,
            'total_liquido' => $totalLiquido,
            'total_inss' => $totalInss,
            'total_inss_patronal' =>
            $totalInssPatronal,
            'total_irps' => $totalIrps,
            'total_outros_descontos' =>
            $totalOutrosDescontos,
            'total_debito' => $totalDebito,
            'total_credito' => $totalCredito,
        ];
    }

    /**
     * Valida se o lançamento fica equilibrado.
     */
    private function validarEquilibrioFolha(
        array $totais
    ): void {
        $diferenca = round(
            (float)$totais['total_debito'] -
                (float)$totais['total_credito'],
            2
        );

        if (abs($diferenca) > 0.01) {
            throw new RuntimeException(
                'A folha salarial não está contabilisticamente equilibrada. ' .
                    'Débitos: ' .
                    number_format(
                        (float)$totais['total_debito'],
                        2,
                        ',',
                        '.'
                    ) .
                    '; Créditos: ' .
                    number_format(
                        (float)$totais['total_credito'],
                        2,
                        ',',
                        '.'
                    ) .
                    '; Diferença: ' .
                    number_format(
                        $diferenca,
                        2,
                        ',',
                        '.'
                    ) .
                    '.'
            );
        }
    }

    /**
     * Obtém o período contabilístico correspondente à folha.
     */
    private function obterPeriodoContabilisticoDaFolha(
        array $folha,
        int $empresaId,
        int $exercicioId,
        int $permitirPeriodoFechado = 0
    ): array {
        $dataReferencia = trim(
            (string)(
                $folha['periodo_data_fim']
                ?? $folha['periodo_data_inicio']
                ?? ''
            )
        );

        if ($dataReferencia === '') {
            throw new RuntimeException(
                'As datas do período salarial não foram encontradas.'
            );
        }

        $sql = "
        SELECT
            pc.*
        FROM periodos_contabilisticos pc
        WHERE pc.empresa_id = :empresa_id
          AND pc.exercicio_id = :exercicio_id
          AND :data_referencia BETWEEN
              pc.data_inicio AND pc.data_fim
        LIMIT 1
    ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':empresa_id' => $empresaId,
            ':exercicio_id' => $exercicioId,
            ':data_referencia' => $dataReferencia,
        ]);

        $periodo = $stmt->fetch(
            PDO::FETCH_ASSOC
        );

        if (!$periodo) {
            throw new RuntimeException(
                'Não foi encontrado um período contabilístico ' .
                    'para a data ' . $dataReferencia . '.'
            );
        }

        if ($permitirPeriodoFechado !== 1) {
            $estado = strtoupper(
                trim(
                    (string)(
                        $periodo['estado']
                        ?? $periodo['status_codigo']
                        ?? ''
                    )
                )
            );

            /*
         * Caso a tabela não tenha uma coluna textual de estado,
         * valida através da tabela status.
         */
            if ($estado === '' && !empty($periodo['status_id'])) {
                $estado = $this->obterCodigoStatus(
                    (int)$periodo['status_id']
                );
            }

            if (
                in_array(
                    $estado,
                    [
                        'FECHADO',
                        'ENCERRADO',
                        'BLOQUEADO',
                        'CANCELADO',
                    ],
                    true
                )
            ) {
                throw new RuntimeException(
                    'O período contabilístico correspondente está fechado.'
                );
            }
        }

        return $periodo;
    }

    /**
     * Obtém o código ou nome de um estado.
     */
    private function obterCodigoStatus(
        int $statusId
    ): string {

        if ($statusId <= 0) {
            return '';
        }

        $sql = "
        SELECT
            nome

        FROM status

        WHERE id = :id

        LIMIT 1
    ";

        $stmt =
            $this->db->prepare(
                $sql
            );

        $stmt->execute([
            ':id' =>
            $statusId
        ]);

        $estado =
            $stmt->fetchColumn();

        return strtoupper(
            trim(
                (string)$estado
            )
        );
    }
    /**
     * Localiza um estado através de possíveis códigos.
     */

    private function obterStatusId(
        array $nomes,
        ?string $tipo = null
    ): int {

        if (empty($nomes)) {
            throw new InvalidArgumentException(
                'Nenhum estado foi informado.'
            );
        }

        $nomes = array_values(
            array_unique(
                array_filter(
                    array_map(
                        static fn(
                            mixed $nome
                        ): string => strtoupper(
                            trim((string)$nome)
                        ),
                        $nomes
                    )
                )
            )
        );

        if (empty($nomes)) {
            throw new InvalidArgumentException(
                'Os estados informados são inválidos.'
            );
        }

        $placeholders = implode(
            ',',
            array_fill(
                0,
                count($nomes),
                '?'
            )
        );

        $sql = "
        SELECT id
        FROM status
        WHERE UPPER(nome)
            IN ({$placeholders})
    ";

        $parametros = $nomes;

        if (
            $tipo !== null
            && trim($tipo) !== ''
        ) {

            $sql .= "
            AND tipo = ?
        ";

            $parametros[] =
                trim($tipo);
        }

        /*
    |--------------------------------------------------------------------------
    | Mantém a prioridade da ordem recebida
    |--------------------------------------------------------------------------
    */

        $sql .= "
        ORDER BY
            FIELD(
                UPPER(nome),
                {$placeholders}
            )
        LIMIT 1
    ";

        $parametros = [
            ...$parametros,
            ...$nomes
        ];

        $stmt =
            $this->db->prepare(
                $sql
            );

        $stmt->execute(
            $parametros
        );

        $statusId =
            (int)$stmt->fetchColumn();

        if ($statusId <= 0) {

            throw new RuntimeException(
                'Não foi encontrado um estado compatível com: '
                    . implode(', ', $nomes)
                    . (
                        $tipo
                        ? ' para o tipo '
                        . $tipo
                        : ''
                    )
                    . '.'
            );
        }

        return $statusId;
    }

    /**
     * Cria o cabeçalho do lançamento contabilístico da folha.
     */
    private function criarLancamentoFolha(
        array $folha,
        int $empresaId,
        int $exercicioId,
        int $periodoContabilisticoId,
        int $diarioId,
        int $numero,
        array $totais,
        ?int $filialId,
        ?int $usuarioId
    ): int {
        $statusId = $this->obterStatusId([
            'CONTABILIZADO',
            'PROCESSADO',
            'CONFIRMADO',
            'ATIVO',
        ]);

        $dataLancamento = trim(
            (string)(
                $folha['periodo_data_fim']
                ?? $folha['gerada_em']
                ?? date('Y-m-d')
            )
        );

        $dataLancamento = substr(
            $dataLancamento,
            0,
            10
        );

        $codigoFolha = trim(
            (string)(
                $folha['codigo']
                ?? 'FOLHA-' . $folha['id']
            )
        );

        $descricao = trim(
            (string)(
                $folha['descricao']
                ?? 'Folha salarial'
            )
        );

        $referencia = 'FOLHA-SALARIAL-' .
            (int)$folha['id'];

        $sql = "
        INSERT INTO lancamentos_contabilisticos (
            empresa_id,
            filial_id,
            exercicio_id,
            periodo_id,
            diario_id,
            numero,
            numero_documento,
            referencia,
            data_lancamento,
            data_documento,
            descricao,
            origem,
            origem_id,
            status_id,
            total_debito,
            total_credito,
            contabilizado_em,
            contabilizado_por,
            criado_por,
            atualizado_por
        )
        VALUES (
            :empresa_id,
            :filial_id,
            :exercicio_id,
            :periodo_id,
            :diario_id,
            :numero,
            :numero_documento,
            :referencia,
            :data_lancamento,
            :data_documento,
            :descricao,
            'FOLHA_SALARIAL',
            :origem_id,
            :status_id,
            :total_debito,
            :total_credito,
            NOW(),
            :contabilizado_por,
            :criado_por,
            :atualizado_por
        )
    ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':empresa_id' => $empresaId,
            ':filial_id' => $filialId,
            ':exercicio_id' => $exercicioId,
            ':periodo_id' => $periodoContabilisticoId,
            ':diario_id' => $diarioId,
            ':numero' => $numero,
            ':numero_documento' => $codigoFolha,
            ':referencia' => $referencia,
            ':data_lancamento' => $dataLancamento,
            ':data_documento' => $dataLancamento,
            ':descricao' => $descricao,
            ':origem_id' => (int)$folha['id'],
            ':status_id' => $statusId,
            ':total_debito' => $totais['total_debito'],
            ':total_credito' => $totais['total_credito'],
            ':contabilizado_por' => $usuarioId,
            ':criado_por' => $usuarioId,
            ':atualizado_por' => $usuarioId,
        ]);

        $lancamentoId = (int)$this->db->lastInsertId();

        if ($lancamentoId <= 0) {
            throw new RuntimeException(
                'Não foi possível criar o lançamento contabilístico.'
            );
        }

        return $lancamentoId;
    }

    /**
     * Insere um item no lançamento contabilístico.
     */
    private function inserirItemLancamento(
        int $lancamentoId,
        int $contaId,
        string $descricao,
        float $debito,
        float $credito,
        int $ordem,
        ?int $centroCustoId = null
    ): void {
        if ($centroCustoId === null) {
            $centroCustoId = $this->centroCustoFolhaAtual;
        }

        $debito = round(
            $debito,
            2
        );

        $credito = round(
            $credito,
            2
        );

        if ($debito < 0 || $credito < 0) {
            throw new RuntimeException(
                'O débito e o crédito não podem ser negativos.'
            );
        }

        if ($debito > 0 && $credito > 0) {
            throw new RuntimeException(
                'Um item não pode possuir débito e crédito simultaneamente.'
            );
        }

        if ($debito <= 0 && $credito <= 0) {
            return;
        }

        $sql = "
        INSERT INTO lancamento_contabilistico_itens (
            lancamento_id,
            conta_contabil_id,
            centro_custo_id,
            cliente_id,
            fornecedor_id,
            descricao,
            debito,
            credito,
            ordem
        )
        VALUES (
            :lancamento_id,
            :conta_id,
            :centro_custo_id,
            NULL,
            NULL,
            :descricao,
            :debito,
            :credito,
            :ordem
        )
    ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':lancamento_id' => $lancamentoId,
            ':conta_id' => $contaId,
            ':centro_custo_id' => $centroCustoId,
            ':descricao' => $descricao,
            ':debito' => $debito,
            ':credito' => $credito,
            ':ordem' => $ordem,
        ]);
    }

    private function obterCentroCustoFolhaRh(
        int $periodoId,
        int $empresaId,
        array $config
    ): ?int {
        if ((int)($config['agrupar_por_centro_custo'] ?? 0) !== 1) {
            return null;
        }

        $sql = "
            SELECT DISTINCT
                COALESCE(c.centro_custo_id, d.centro_custo_id)
                    AS centro_custo_id
            FROM processamentos_salariais ps
            INNER JOIN funcionarios f
                ON f.id = ps.funcionario_id
               AND f.empresa_id = ps.empresa_id
            LEFT JOIN cargos c
                ON c.id = f.cargo_id
               AND c.empresa_id = f.empresa_id
            LEFT JOIN departamentos d
                ON d.id = f.departamento_id
               AND d.empresa_id = f.empresa_id
            WHERE ps.empresa_id = :empresa_id
              AND ps.periodo_id = :periodo_id
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':empresa_id' => $empresaId,
            ':periodo_id' => $periodoId,
        ]);

        $centros = $stmt->fetchAll(PDO::FETCH_COLUMN);
        $centrosValidos = [];
        $semCentro = false;

        foreach ($centros as $centro) {
            if ($centro === null || (int)$centro <= 0) {
                $semCentro = true;
                continue;
            }

            $centrosValidos[(int)$centro] = (int)$centro;
        }

        if ($semCentro || $centrosValidos === []) {
            throw new RuntimeException(
                'Existem trabalhadores da folha sem centro de custo configurado no cargo ou departamento.'
            );
        }

        if (count($centrosValidos) > 1) {
            throw new RuntimeException(
                'A folha possui vários centros de custo. Desdobre os totais contabilísticos por centro antes de contabilizar.'
            );
        }

        $centroCustoId = (int)reset($centrosValidos);

        $stmt = $this->db->prepare("
            SELECT COUNT(*)
            FROM centros_custo
            WHERE id = :centro_custo_id
              AND empresa_id = :empresa_id
        ");
        $stmt->execute([
            ':centro_custo_id' => $centroCustoId,
            ':empresa_id' => $empresaId,
        ]);

        if ((int)$stmt->fetchColumn() !== 1) {
            throw new RuntimeException(
                'O centro de custo da folha não pertence à empresa.'
            );
        }

        return $centroCustoId;
    }

    /**
     * Cria os débitos e créditos da folha salarial.
     */

    private function criarItensLancamentoFolhaRh(
        int $lancamentoId,
        array $folha,
        array $contas,
        array $rubricas,
        int $empresaId,
        array $config
    ): array {

        $ordem = 1;

        $totalDebito = 0.00;
        $totalCredito = 0.00;

        $periodo =
            trim(
                (string)(
                    $folha['periodo_nome']
                    ?? $folha['periodo_codigo']
                    ?? $folha['codigo']
                )
            );

        $contaSalariosPagar =
            $contas['SALARIOS_PAGAR'];

        $centroCustoId =
            $this->obterCentroCustoFolhaRh(
                (int)$folha['periodo_id'],
                $empresaId,
                $config
            );

        $this->centroCustoFolhaAtual = $centroCustoId;

        /*
    |--------------------------------------------------------------------------
    | 1. Salário base
    |--------------------------------------------------------------------------
    |
    | D — Gasto de salário base
    | C — Remunerações a pagar
    |
    */

        $salarioBase =
            round(
                (float)(
                    $folha['total_salario_base']
                    ?? 0
                ),
                2
            );

        /*
        |--------------------------------------------------------------------------
        | Faltas e atrasos
        |--------------------------------------------------------------------------
        |
        | Faltas e atrasos reduzem a remuneração adquirida pelo trabalhador.
        | Não constituem uma obrigação da empresa e, por isso, não devem ser
        | creditados em "Outras operações com os trabalhadores".
        |
        */

        $descontosAssiduidade = 0.00;

        foreach ($rubricas as $rubrica) {
            $categoria = strtoupper(
                trim((string)($rubrica['categoria'] ?? ''))
            );

            if (in_array($categoria, ['FALTA', 'ATRASO'], true)) {
                $descontosAssiduidade += round(
                    abs((float)($rubrica['valor_total'] ?? 0)),
                    2
                );
            }
        }

        $descontosAssiduidade = round($descontosAssiduidade, 2);

        if ($descontosAssiduidade > $salarioBase) {
            throw new RuntimeException(
                'O total de faltas e atrasos não pode ser superior ao salário base.'
            );
        }

        $salarioBaseReconhecido = round(
            $salarioBase - $descontosAssiduidade,
            2
        );

        if ($salarioBaseReconhecido > 0) {

            $this->inserirItemLancamento(
                $lancamentoId,
                (int)$contas['SALARIO_BASE']['id'],
                'Salário base - ' . $periodo,
                $salarioBaseReconhecido,
                0,
                $ordem++
            );

            $totalDebito += $salarioBaseReconhecido;

            $this->inserirItemLancamento(
                $lancamentoId,
                (int)$contaSalariosPagar['id'],
                'Salário base a pagar - ' . $periodo,
                0,
                $salarioBaseReconhecido,
                $ordem++
            );

            $totalCredito += $salarioBaseReconhecido;
        }


        /*
    |--------------------------------------------------------------------------
    | 2. Rubricas configuradas
    |--------------------------------------------------------------------------
    */

        foreach ($rubricas as $rubrica) {

            $valor =
                round(
                    abs(
                        (float)(
                            $rubrica['valor_total']
                            ?? 0
                        )
                    ),
                    2
                );

            if ($valor <= 0) {
                continue;
            }

            $tipo =
                strtoupper(
                    trim(
                        (string)(
                            $rubrica['tipo']
                            ?? ''
                        )
                    )
                );

            $categoria =
                strtoupper(
                    trim(
                        (string)(
                            $rubrica['categoria']
                            ?? ''
                        )
                    )
                );

            $nome =
                trim(
                    (string)(
                        $rubrica['nome']
                        ?? $rubrica['codigo']
                        ?? 'Rubrica'
                    )
                );

            /*
             * Faltas e atrasos já foram deduzidos do salário base acima.
             * Não devem gerar movimentos em contas do passivo.
             */
            if (
                in_array(
                    $categoria,
                    ['FALTA', 'ATRASO'],
                    true
                )
            ) {
                continue;
            }


            /*
         * Toda rubrica com valor deve estar
         * contabilisticamente configurada.
         */

            if (
                empty($rubrica['mapeamento_id'])
            ) {
                throw new RuntimeException(
                    'A rubrica "'
                        . $nome
                        . '" não possui configuração contabilística.'
                );
            }

            $estado =
                strtoupper(
                    trim(
                        (string)(
                            $rubrica['mapeamento_estado']
                            ?? ''
                        )
                    )
                );

            if (
                in_array(
                    $estado,
                    [
                        'INACTIVO',
                        'INATIVO',
                        'CANCELADO'
                    ],
                    true
                )
            ) {
                throw new RuntimeException(
                    'O mapeamento contabilístico da rubrica "'
                        . $nome
                        . '" não está activo.'
                );
            }


            $usarSalarios =
                (int)(
                    $rubrica['usar_conta_salarios_pagar']
                    ?? 0
                ) === 1;


            /*
        |--------------------------------------------------------------------------
        | PROVENTO
        |--------------------------------------------------------------------------
        |
        | D — conta configurada da rubrica
        | C — salários a pagar
        |
        */

            if ($tipo === 'PROVENTO') {

                $contaDebitoId =
                    (int)(
                        $rubrica['conta_debito_id']
                        ?? 0
                    );

                if ($contaDebitoId <= 0) {
                    throw new RuntimeException(
                        'A rubrica de provento "'
                            . $nome
                            . '" não possui conta de débito.'
                    );
                }

                $contaDebito =
                    $this->obterContaRhPorId(
                        $contaDebitoId,
                        $empresaId
                    );

                $this->inserirItemLancamento(
                    $lancamentoId,
                    (int)$contaDebito['id'],
                    $nome . ' - ' . $periodo,
                    $valor,
                    0,
                    $ordem++
                );

                $totalDebito += $valor;


                if ($usarSalarios) {

                    $this->inserirItemLancamento(
                        $lancamentoId,
                        (int)$contaSalariosPagar['id'],
                        $nome . ' a pagar - ' . $periodo,
                        0,
                        $valor,
                        $ordem++
                    );
                } else {

                    $contaCreditoId =
                        (int)(
                            $rubrica['conta_credito_id']
                            ?? 0
                        );

                    if ($contaCreditoId <= 0) {
                        throw new RuntimeException(
                            'A rubrica de provento "'
                                . $nome
                                . '" não possui contrapartida.'
                        );
                    }

                    $contaCredito =
                        $this->obterContaRhPorId(
                            $contaCreditoId,
                            $empresaId
                        );

                    $this->inserirItemLancamento(
                        $lancamentoId,
                        (int)$contaCredito['id'],
                        $nome . ' - ' . $periodo,
                        0,
                        $valor,
                        $ordem++
                    );
                }

                $totalCredito += $valor;

                continue;
            }


            /*
        |--------------------------------------------------------------------------
        | DESCONTO
        |--------------------------------------------------------------------------
        |
        | D — salários a pagar
        | C — conta configurada da rubrica
        |
        */

            if ($tipo === 'DESCONTO') {

                if ($usarSalarios) {

                    $this->inserirItemLancamento(
                        $lancamentoId,
                        (int)$contaSalariosPagar['id'],
                        $nome . ' - ' . $periodo,
                        $valor,
                        0,
                        $ordem++
                    );
                } else {

                    $contaDebitoId =
                        (int)(
                            $rubrica['conta_debito_id']
                            ?? 0
                        );

                    if ($contaDebitoId <= 0) {
                        throw new RuntimeException(
                            'A rubrica de desconto "'
                                . $nome
                                . '" não possui conta de débito.'
                        );
                    }

                    $contaDebito =
                        $this->obterContaRhPorId(
                            $contaDebitoId,
                            $empresaId
                        );

                    $this->inserirItemLancamento(
                        $lancamentoId,
                        (int)$contaDebito['id'],
                        $nome . ' - ' . $periodo,
                        $valor,
                        0,
                        $ordem++
                    );
                }

                $totalDebito += $valor;


                $contaCreditoId =
                    (int)(
                        $rubrica['conta_credito_id']
                        ?? 0
                    );

                if ($contaCreditoId <= 0) {
                    throw new RuntimeException(
                        'A rubrica de desconto "'
                            . $nome
                            . '" não possui conta de crédito.'
                    );
                }

                $contaCredito =
                    $this->obterContaRhPorId(
                        $contaCreditoId,
                        $empresaId
                    );

                $this->inserirItemLancamento(
                    $lancamentoId,
                    (int)$contaCredito['id'],
                    $nome . ' - ' . $periodo,
                    0,
                    $valor,
                    $ordem++
                );

                $totalCredito += $valor;
            }


            /*
|--------------------------------------------------------------------------
| ENCARGO
|--------------------------------------------------------------------------
|
| D — conta de gasto/encargo patronal
| C — conta da obrigação
|
*/

            if ($tipo === 'ENCARGO') {

                $contaDebitoId =
                    (int)(
                        $rubrica['conta_debito_id']
                        ?? 0
                    );

                $contaCreditoId =
                    (int)(
                        $rubrica['conta_credito_id']
                        ?? 0
                    );

                if ($contaDebitoId <= 0) {
                    throw new RuntimeException(
                        'A rubrica de encargo "'
                            . $nome
                            . '" não possui conta de débito.'
                    );
                }

                if ($contaCreditoId <= 0) {
                    throw new RuntimeException(
                        'A rubrica de encargo "'
                            . $nome
                            . '" não possui conta de crédito.'
                    );
                }

                $contaDebito =
                    $this->obterContaRhPorId(
                        $contaDebitoId,
                        $empresaId
                    );

                $contaCredito =
                    $this->obterContaRhPorId(
                        $contaCreditoId,
                        $empresaId
                    );

                $this->inserirItemLancamento(
                    $lancamentoId,
                    (int)$contaDebito['id'],
                    $nome . ' - ' . $periodo,
                    $valor,
                    0,
                    $ordem++
                );

                $totalDebito += $valor;

                $this->inserirItemLancamento(
                    $lancamentoId,
                    (int)$contaCredito['id'],
                    $nome . ' - ' . $periodo,
                    0,
                    $valor,
                    $ordem++
                );

                $totalCredito += $valor;

                continue;
            }
        }


        /*
    |--------------------------------------------------------------------------
    | Validação final
    |--------------------------------------------------------------------------
    */

        $totalDebito =
            round($totalDebito, 2);

        $totalCredito =
            round($totalCredito, 2);

        if (
            abs(
                $totalDebito
                    - $totalCredito
            ) > 0.01
        ) {
            throw new RuntimeException(
                'O lançamento da folha não ficou equilibrado. '
                    . 'Débito: '
                    . number_format(
                        $totalDebito,
                        2,
                        ',',
                        '.'
                    )
                    . '; Crédito: '
                    . number_format(
                        $totalCredito,
                        2,
                        ',',
                        '.'
                    )
                    . '.'
            );
        }

        $this->centroCustoFolhaAtual = null;

        return [
            'total_debito' =>
            $totalDebito,

            'total_credito' =>
            $totalCredito
        ];
    }

    /**
     * Regista a relação entre a folha e o lançamento.
     */
    private function criarIntegracaoFolha(
        array $folha,
        int $lancamentoId,
        array $totais,
        ?int $usuarioId
    ): int {
        $statusId =
            $this->obterStatusId(
                [
                    'CONTABILIZADO',
                    'PROCESSADO',
                    'CONFIRMADO',
                    'ATIVO'
                ],
                'lancamento_contabilistico'
            );

        $sql = "
        INSERT INTO integracoes_contabilisticas_folha (
            uuid,
            empresa_id,
            periodo_salarial_id,
            folha_salarial_id,
            lancamento_contabilistico_id,
            total_salario_base,
            total_subsidios,
            total_horas_extras,
            total_outros_proventos,
            total_inss,
            total_inss_patronal,
            total_irps,
            total_outros_descontos,
            total_bruto,
            total_liquido,
            total_debito,
            total_credito,
            status_id,
            contabilizado_por,
            contabilizado_em,
            mensagem_erro
        )
        VALUES (
            :uuid,
            :empresa_id,
            :periodo_salarial_id,
            :folha_salarial_id,
            :lancamento_id,
            :total_salario_base,
            :total_subsidios,
            :total_horas_extras,
            :total_outros_proventos,
            :total_inss,
            :total_inss_patronal,
            :total_irps,
            :total_outros_descontos,
            :total_bruto,
            :total_liquido,
            :total_debito,
            :total_credito,
            :status_id,
            :contabilizado_por,
            NOW(),
            NULL
        )
    ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':uuid' => $this->gerarUuid(),
            ':empresa_id' => (int)$folha['empresa_id'],
            ':periodo_salarial_id' =>
            (int)$folha['periodo_id'],
            ':folha_salarial_id' =>
            (int)$folha['id'],
            ':lancamento_id' => $lancamentoId,
            ':total_salario_base' =>
            $totais['total_salario_base'],
            ':total_subsidios' =>
            $totais['total_subsidios'],
            ':total_horas_extras' =>
            $totais['total_horas_extras'],
            ':total_outros_proventos' =>
            $totais['total_outros_proventos'],
            ':total_inss' =>
            $totais['total_inss'],
            ':total_inss_patronal' =>
            $totais['total_inss_patronal'],
            ':total_irps' =>
            $totais['total_irps'],
            ':total_outros_descontos' =>
            $totais['total_outros_descontos'],
            ':total_bruto' =>
            $totais['total_bruto'],
            ':total_liquido' =>
            $totais['total_liquido'],
            ':total_debito' =>
            $totais['total_debito'],
            ':total_credito' =>
            $totais['total_credito'],
            ':status_id' => $statusId,
            ':contabilizado_por' => $usuarioId,
        ]);

        $integracaoId = (int)
        $this->db->lastInsertId();

        if ($integracaoId <= 0) {
            throw new RuntimeException(
                'Não foi possível registar a integração da folha.'
            );
        }

        return $integracaoId;
    }

    /**
     * Contabiliza um pagamento salarial confirmado.
     */
    public function contabilizarPagamento(
        int $pagamentoId,
        int $empresaId,
        ?int $usuarioId
    ): array {
        if ($pagamentoId <= 0) {
            throw new InvalidArgumentException(
                'O ID do pagamento salarial é obrigatório.'
            );
        }

        if ($empresaId <= 0) {
            throw new InvalidArgumentException(
                'A empresa não foi identificada.'
            );
        }

        try {
            $this->iniciarTransacao();

            /*
        |--------------------------------------------------------------------------
        | Obter pagamento
        |--------------------------------------------------------------------------
        */

            $pagamento = $this->obterPagamentoEmpresa(
                $pagamentoId,
                $empresaId
            );

            /*
        |--------------------------------------------------------------------------
        | Validar confirmação
        |--------------------------------------------------------------------------
        */

            if (empty($pagamento['confirmado_em'])) {
                throw new RuntimeException(
                    'O pagamento salarial ainda não foi confirmado.'
                );
            }

            if ((float)$pagamento['valor_pago'] <= 0) {
                throw new RuntimeException(
                    'O valor pago deve ser superior a zero.'
                );
            }

            /*
        |--------------------------------------------------------------------------
        | Verificar contabilização anterior
        |--------------------------------------------------------------------------
        */

            if (
                !empty($pagamento['lancamento_contabilistico_id'])
            ) {
                throw new RuntimeException(
                    'Este pagamento salarial já foi contabilizado.'
                );
            }

            if (
                $this->pagamentoJaContabilizado(
                    $pagamentoId,
                    $empresaId
                )
            ) {
                throw new RuntimeException(
                    'Já existe um lançamento contabilístico para este pagamento.'
                );
            }

            /*
        /*
|--------------------------------------------------------------------------
| Configuração contabilística do RH
|--------------------------------------------------------------------------
*/

            $configuracaoRh =
                $this->obterConfiguracaoRh(
                    $empresaId
                );


            /*
|--------------------------------------------------------------------------
| Diário contabilístico do RH
|--------------------------------------------------------------------------
*/

            $diario =
                $this->obterDiarioPagamentoRh(
                    $empresaId,
                    $configuracaoRh
                );


            /*
|--------------------------------------------------------------------------
| Período contabilístico
|--------------------------------------------------------------------------
*/

            $periodo =
                $this->obterPeriodoContabilisticoPagamentoRh(
                    $empresaId,
                    (string)$pagamento['data_pagamento']
                );


            $exercicioId =
                (int)(
                    $periodo['exercicio_id']
                    ?? 0
                );


            if ($exercicioId <= 0) {
                throw new RuntimeException(
                    'O período contabilístico não possui exercício associado.'
                );
            }


            /*
|--------------------------------------------------------------------------
| Conta de salários a pagar
|--------------------------------------------------------------------------
*/

            $contaSalariosPagarId =
                (int)(
                    $configuracaoRh['conta_salarios_pagar_id']
                    ?? 0
                );


            if ($contaSalariosPagarId <= 0) {
                throw new RuntimeException(
                    'A conta de salários a pagar não está configurada no RH.'
                );
            }


            $contaSalariosPagar =
                $this->obterContaRhPorId(
                    $contaSalariosPagarId,
                    $empresaId
                );


            /*
|--------------------------------------------------------------------------
| Conta utilizada no pagamento
|--------------------------------------------------------------------------
*/

            $contaPagamento =
                $this->obterContaPagamentoRh(
                    $pagamento,
                    $empresaId,
                    $configuracaoRh
                );
            /*
        |--------------------------------------------------------------------------
        | Numeração
        |--------------------------------------------------------------------------
        */

            $numeroDiario = $this->proximoNumeroDiario(
                (int)$diario['id']
            );

            /*
        |--------------------------------------------------------------------------
        | Criar lançamento
        |--------------------------------------------------------------------------
        */

            $lancamentoId =
                $this->criarLancamentoPagamento(
                    $pagamento,
                    $empresaId,
                    $exercicioId,
                    (int)$periodo['id'],
                    (int)$diario['id'],
                    $numeroDiario,
                    $usuarioId
                );

            /*
        |--------------------------------------------------------------------------
        | Criar itens
        |--------------------------------------------------------------------------
        */

            $this->criarItensPagamento(
                $lancamentoId,
                $pagamento,
                $contaSalariosPagar,
                $contaPagamento
            );

            /*
             * O lançamento contabilístico reduz a conta 1.2.1, mas o
             * módulo bancário mantém saldo e extrato próprios. Registamos
             * a saída na mesma transação para os dois módulos nunca
             * ficarem divergentes.
             */
            $this->registarMovimentoBancarioPagamento(
                $pagamento,
                $empresaId,
                $configuracaoRh,
                $usuarioId
            );

            /*
        |--------------------------------------------------------------------------
        | Atualizar pagamento
        |--------------------------------------------------------------------------
        */

            $this->associarLancamentoPagamento(
                $pagamentoId,
                $empresaId,
                $lancamentoId
            );

            $this->confirmarTransacao();

            return [
                'pagamento_id' => $pagamentoId,
                'lancamento_contabilistico_id' =>
                $lancamentoId,
                'numero_lancamento' => $numeroDiario,
                'diario' => $diario['codigo'],
                'conta_creditada' =>
                $contaPagamento['codigo'],
                'valor' => round(
                    (float)$pagamento['valor_pago'],
                    2
                ),
                'mensagem' =>
                'Pagamento salarial contabilizado com sucesso.',
            ];
        } catch (Throwable $erro) {
            $this->cancelarTransacao();

            throw $erro;
        }
    }

    private function obterContaRhPorId(
        int $contaId,
        int $empresaId
    ): array {

        if ($contaId <= 0) {
            throw new RuntimeException(
                'Conta contabilística inválida.'
            );
        }


        $sql = "
        SELECT
            cc.id,
            cc.codigo,
            cc.nome,
            cc.plano_contas_id,
            cc.integracao_codigo,
            cc.tipo,
            cc.natureza,
            cc.grupo,
            cc.aceita_lancamento,
            cc.conta_caixa,
            cc.conta_banco

        FROM contas_contabilisticas cc

        WHERE cc.id =
            :conta_id

          AND cc.empresa_id =
            :empresa_id

        LIMIT 1
    ";


        $stmt =
            $this->db->prepare($sql);


        $stmt->execute([
            ':conta_id' =>
            $contaId,

            ':empresa_id' =>
            $empresaId
        ]);


        $conta =
            $stmt->fetch(
                PDO::FETCH_ASSOC
            );


        if (!$conta) {
            throw new RuntimeException(
                'A conta contabilística configurada no RH não foi encontrada.'
            );
        }


        if (
            strtoupper(
                trim(
                    (string)$conta['tipo']
                )
            ) !== 'ANALITICA'
            ||
            (int)$conta['aceita_lancamento']
            !== 1
        ) {
            throw new RuntimeException(
                'A conta contabilística configurada no RH não permite lançamentos.'
            );
        }


        return $conta;
    }

    private function obterDiarioPagamentoRh(
        int $empresaId,
        array $configuracaoRh
    ): array {

        $diarioId =
            (int)(
                $configuracaoRh['diario_contabilistico_id']
                ?? 0
            );


        if ($diarioId <= 0) {
            throw new RuntimeException(
                'O diário contabilístico do RH não está configurado.'
            );
        }


        $sql = "
        SELECT
            dc.*

        FROM diarios_contabilisticos dc

        WHERE dc.id =
            :diario_id

          AND dc.empresa_id =
            :empresa_id

        LIMIT 1
    ";


        $stmt =
            $this->db->prepare($sql);


        $stmt->execute([
            ':diario_id' =>
            $diarioId,

            ':empresa_id' =>
            $empresaId
        ]);


        $diario =
            $stmt->fetch(
                PDO::FETCH_ASSOC
            );


        if (!$diario) {
            throw new RuntimeException(
                'O diário contabilístico configurado no RH não foi encontrado.'
            );
        }


        return $diario;
    }


    private function obterPeriodoContabilisticoPagamentoRh(
        int $empresaId,
        string $data
    ): array {

        $data =
            substr(
                trim($data),
                0,
                10
            );


        if (
            !preg_match(
                '/^\d{4}-\d{2}-\d{2}$/',
                $data
            )
        ) {
            throw new RuntimeException(
                'A data do pagamento é inválida.'
            );
        }


        $sql = "
        SELECT
            pc.*

        FROM periodos_contabilisticos pc

        WHERE pc.empresa_id =
            :empresa_id

          AND :data_pagamento
              BETWEEN
                  pc.data_inicio
                  AND pc.data_fim

        ORDER BY
            pc.data_inicio DESC,
            pc.id DESC

        LIMIT 1
    ";


        $stmt =
            $this->db->prepare($sql);


        $stmt->execute([
            ':empresa_id' =>
            $empresaId,

            ':data_pagamento' =>
            $data
        ]);


        $periodo =
            $stmt->fetch(
                PDO::FETCH_ASSOC
            );


        if (!$periodo) {
            throw new RuntimeException(
                'Não existe período contabilístico para a data '
                    . $data
                    . '.'
            );
        }


        /*
     * Não permitir pagamento num período
     * contabilístico encerrado.
     */

        if (!empty($periodo['status_id'])) {

            $estado =
                $this->obterCodigoStatus(
                    (int)$periodo['status_id']
                );


            if (
                in_array(
                    $estado,
                    [
                        'FECHADO',
                        'ENCERRADO',
                        'BLOQUEADO',
                        'CANCELADO'
                    ],
                    true
                )
            ) {
                throw new RuntimeException(
                    'O período contabilístico do pagamento está fechado.'
                );
            }
        }


        return $periodo;
    }


    private function obterContaPagamentoRh(
        array $pagamento,
        int $empresaId,
        array $configuracaoRh
    ): array {

        $formaPagamento =
            strtoupper(
                trim(
                    (string)(
                        $pagamento['forma_pagamento']
                        ?? ''
                    )
                )
            );


        $contaPagamentoId =
            (int)(
                $pagamento['conta_contabil_id']
                ?? 0
            );


        /*
     * Se o pagamento já possui conta,
     * ela é a fonte principal.
     */

        if ($contaPagamentoId <= 0) {

            if ($formaPagamento === 'NUMERARIO') {

                $contaPagamentoId =
                    (int)(
                        $configuracaoRh['conta_caixa_padrao_id']
                        ?? 0
                    );
            } else {

                $contaPagamentoId =
                    (int)(
                        $configuracaoRh['conta_banco_padrao_id']
                        ?? 0
                    );
            }
        }


        if ($contaPagamentoId <= 0) {

            throw new RuntimeException(
                $formaPagamento === 'NUMERARIO'
                    ? 'A conta de caixa padrão não está configurada no RH.'
                    : 'A conta bancária padrão não está configurada no RH.'
            );
        }


        $conta =
            $this->obterContaRhPorId(
                $contaPagamentoId,
                $empresaId
            );


        /*
     * Numerário → Caixa
     */

        if (
            $formaPagamento === 'NUMERARIO'
            &&
            (int)(
                $conta['conta_caixa']
                ?? 0
            ) !== 1
        ) {
            throw new RuntimeException(
                'Pagamentos em numerário devem utilizar uma conta de Caixa.'
            );
        }


        /*
     * Outros métodos → Banco
     */

        if (
            $formaPagamento !== 'NUMERARIO'
            &&
            (int)(
                $conta['conta_banco']
                ?? 0
            ) !== 1
        ) {
            throw new RuntimeException(
                'Esta forma de pagamento deve utilizar uma conta bancária.'
            );
        }


        return $conta;
    }
    /**
     * Obtém um pagamento salarial pertencente à empresa.
     */
    private function obterPagamentoEmpresa(
        int $pagamentoId,
        int $empresaId
    ): array {
        $sql = "
        SELECT
            pg.*,

            ps.funcionario_id,
            ps.funcionario_nome,
            ps.periodo_id AS periodo_salarial_id,

            fn.filial_id AS funcionario_filial_id,

            rc.codigo AS recibo_numero

        FROM pagamentos_salariais pg

        INNER JOIN processamentos_salariais ps
            ON ps.id = pg.processamento_id
           AND ps.empresa_id = pg.empresa_id

        INNER JOIN funcionarios fn
            ON fn.id = ps.funcionario_id
           AND fn.empresa_id = pg.empresa_id

        LEFT JOIN recibos_salarios rc
            ON rc.id = pg.recibo_id

        WHERE pg.id = :pagamento_id
          AND pg.empresa_id = :empresa_id

        LIMIT 1
    ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':pagamento_id' => $pagamentoId,
            ':empresa_id' => $empresaId,
        ]);

        $pagamento = $stmt->fetch(
            PDO::FETCH_ASSOC
        );

        if (!$pagamento) {
            throw new RuntimeException(
                'Pagamento salarial não encontrado para esta empresa.'
            );
        }

        return $pagamento;
    }

    /**
     * Verifica se já existe lançamento para o pagamento.
     */
    private function pagamentoJaContabilizado(
        int $pagamentoId,
        int $empresaId
    ): bool {
        $sql = "
        SELECT COUNT(*)
        FROM lancamentos_contabilisticos
        WHERE empresa_id = :empresa_id
          AND origem = 'PAGAMENTO_SALARIAL'
          AND origem_id = :pagamento_id
    ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':empresa_id' => $empresaId,
            ':pagamento_id' => $pagamentoId,
        ]);

        return (int)$stmt->fetchColumn() > 0;
    }

    /**
     * Define a conta de contrapartida pela forma de pagamento.
     */
    private function obterCodigoContaPagamento(
        string $formaPagamento
    ): string {
        $formaPagamento = strtoupper(
            trim($formaPagamento)
        );

        if ($formaPagamento === 'NUMERARIO') {
            return 'CAIXA';
        }

        return 'BANCOS';
    }

    /**
     * Localiza uma conta analítica dentro do plano configurado.
     */
    private function obterContaPlano(
        int $empresaId,
        int $planoContasId,
        string $integracaoCodigo
    ): array {
        $sql = "
        SELECT
            id,
            codigo,
            nome,
            integracao_codigo,
            tipo,
            natureza,
            aceita_lancamento
        FROM contas_contabilisticas
        WHERE empresa_id = :empresa_id
          AND plano_contas_id = :plano_contas_id
          AND integracao_codigo =
              :integracao_codigo
        LIMIT 1
    ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':empresa_id' => $empresaId,
            ':plano_contas_id' => $planoContasId,
            ':integracao_codigo' =>
            $integracaoCodigo,
        ]);

        $conta = $stmt->fetch(
            PDO::FETCH_ASSOC
        );

        if (!$conta) {
            throw new RuntimeException(
                "A conta de integração {$integracaoCodigo} " .
                    'não foi encontrada no plano configurado.'
            );
        }

        if (
            $conta['tipo'] !== 'ANALITICA' ||
            (int)$conta['aceita_lancamento'] !== 1
        ) {
            throw new RuntimeException(
                "A conta {$integracaoCodigo} não permite lançamentos."
            );
        }

        return $conta;
    }

    /**
     * Obtém o diário adequado ao pagamento.
     */
    private function obterDiarioPagamento(
        int $empresaId,
        array $pagamento,
        array $configuracao
    ): array {
        $formaPagamento = strtoupper(
            trim(
                (string)$pagamento['forma_pagamento']
            )
        );

        if ($formaPagamento === 'NUMERARIO') {
            $diarioId = (int)(
                $configuracao['diario_caixa_id']
                ?? 0
            );

            $tipoEsperado = 'CAIXA';
        } else {
            $diarioId = (int)(
                $configuracao['diario_bancos_id']
                ?? 0
            );

            $tipoEsperado = 'BANCOS';
        }

        if ($diarioId <= 0) {
            throw new RuntimeException(
                "O diário de {$tipoEsperado} não está configurado."
            );
        }

        $sql = "
        SELECT *
        FROM diarios_contabilisticos
        WHERE id = :diario_id
          AND empresa_id = :empresa_id
          AND tipo = :tipo
        LIMIT 1
    ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':diario_id' => $diarioId,
            ':empresa_id' => $empresaId,
            ':tipo' => $tipoEsperado,
        ]);

        $diario = $stmt->fetch(
            PDO::FETCH_ASSOC
        );

        if (!$diario) {
            throw new RuntimeException(
                'O diário configurado para o pagamento ' .
                    'não foi encontrado.'
            );
        }

        return $diario;
    }

    /**
     * Obtém o período contabilístico correspondente a uma data.
     */
    private function obterPeriodoContabilisticoData(
        int $empresaId,
        int $exercicioId,
        string $data,
        int $permitirPeriodoFechado = 0
    ): array {
        $data = substr(
            trim($data),
            0,
            10
        );

        if (
            !preg_match(
                '/^\d{4}-\d{2}-\d{2}$/',
                $data
            )
        ) {
            throw new RuntimeException(
                'A data do pagamento é inválida.'
            );
        }

        $sql = "
        SELECT *
        FROM periodos_contabilisticos
        WHERE empresa_id = :empresa_id
          AND exercicio_id = :exercicio_id
          AND :data_pagamento BETWEEN
              data_inicio AND data_fim
        LIMIT 1
    ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':empresa_id' => $empresaId,
            ':exercicio_id' => $exercicioId,
            ':data_pagamento' => $data,
        ]);

        $periodo = $stmt->fetch(
            PDO::FETCH_ASSOC
        );

        if (!$periodo) {
            throw new RuntimeException(
                'Não foi encontrado período contabilístico ' .
                    "para a data {$data}."
            );
        }

        if ($permitirPeriodoFechado !== 1) {
            $estado = '';

            if (!empty($periodo['status_id'])) {
                $estado = $this->obterCodigoStatus(
                    (int)$periodo['status_id']
                );
            }

            if (
                in_array(
                    $estado,
                    [
                        'FECHADO',
                        'ENCERRADO',
                        'BLOQUEADO',
                        'CANCELADO',
                    ],
                    true
                )
            ) {
                throw new RuntimeException(
                    'O período contabilístico do pagamento está fechado.'
                );
            }
        }

        return $periodo;
    }

    /**
     * Cria o cabeçalho do lançamento do pagamento salarial.
     */
    private function criarLancamentoPagamento(
        array $pagamento,
        int $empresaId,
        int $exercicioId,
        int $periodoContabilisticoId,
        int $diarioId,
        int $numeroDiario,
        ?int $usuarioId
    ): int {
        $statusId =
            $this->obterStatusId(
                [
                    'CONTABILIZADO',
                    'PROCESSADO',
                    'CONFIRMADO',
                    'ATIVO'
                ],
                'lancamento_contabilistico'
            );

        $valorPago = round(
            (float)$pagamento['valor_pago'],
            2
        );

        $numeroPagamento = trim(
            (string)(
                $pagamento['numero']
                ?? $pagamento['codigo']
                ?? 'PAGAMENTO-' . $pagamento['id']
            )
        );

        $funcionarioNome = trim(
            (string)(
                $pagamento['funcionario_nome']
                ?? ''
            )
        );

        $descricao = 'Pagamento salarial';

        if ($funcionarioNome !== '') {
            $descricao .= ' - ' . $funcionarioNome;
        }

        $sql = "
        INSERT INTO lancamentos_contabilisticos (
            empresa_id,
            filial_id,
            exercicio_id,
            periodo_id,
            diario_id,
            numero,
            numero_documento,
            referencia,
            data_lancamento,
            data_documento,
            descricao,
            origem,
            origem_id,
            status_id,
            total_debito,
            total_credito,
            contabilizado_em,
            contabilizado_por,
            criado_por,
            atualizado_por
        )
        VALUES (
            :empresa_id,
            :filial_id,
            :exercicio_id,
            :periodo_id,
            :diario_id,
            :numero,
            :numero_documento,
            :referencia,
            :data_lancamento,
            :data_documento,
            :descricao,
            'PAGAMENTO_SALARIAL',
            :origem_id,
            :status_id,
            :total_debito,
            :total_credito,
            NOW(),
            :contabilizado_por,
            :criado_por,
            :atualizado_por
        )
    ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':empresa_id' => $empresaId,
            ':filial_id' =>
            !empty($pagamento['funcionario_filial_id'])
                ? (int)$pagamento['funcionario_filial_id']
                : null,
            ':exercicio_id' => $exercicioId,
            ':periodo_id' =>
            $periodoContabilisticoId,
            ':diario_id' => $diarioId,
            ':numero' => $numeroDiario,
            ':numero_documento' =>
            $numeroPagamento,
            ':referencia' =>
            'PAGAMENTO-SALARIAL-' .
                (int)$pagamento['id'],
            ':data_lancamento' =>
            $pagamento['data_pagamento'],
            ':data_documento' =>
            $pagamento['data_pagamento'],
            ':descricao' => $descricao,
            ':origem_id' =>
            (int)$pagamento['id'],
            ':status_id' => $statusId,
            ':total_debito' => $valorPago,
            ':total_credito' => $valorPago,
            ':contabilizado_por' => $usuarioId,
            ':criado_por' => $usuarioId,
            ':atualizado_por' => $usuarioId,
        ]);

        $lancamentoId = (int)
        $this->db->lastInsertId();

        if ($lancamentoId <= 0) {
            throw new RuntimeException(
                'Não foi possível criar o lançamento do pagamento salarial.'
            );
        }

        return $lancamentoId;
    }

    /**
     * Regista a saída no extrato e atualiza o saldo do banco operacional.
     * Pagamentos em numerário pertencem ao módulo de Caixa e não entram
     * nesta rotina.
     */
    private function registarMovimentoBancarioPagamento(
        array $pagamento,
        int $empresaId,
        array $configuracaoRh,
        ?int $usuarioId
    ): void {
        $formaPagamento = strtoupper(
            trim((string)($pagamento['forma_pagamento'] ?? ''))
        );

        if ($formaPagamento === 'NUMERARIO') {
            return;
        }

        $bancoId = (int)(
            $configuracaoRh['banco_pagamento_padrao_id']
            ?? 0
        );

        if ($bancoId <= 0) {
            throw new RuntimeException(
                'Configure o banco operacional padrão para pagamentos no RH.'
            );
        }

        if ($usuarioId === null || $usuarioId <= 0) {
            throw new RuntimeException(
                'Utilizador do pagamento salarial não identificado.'
            );
        }

        $numero = trim((string)(
            $pagamento['numero']
            ?? $pagamento['codigo']
            ?? 'PAGAMENTO-' . (int)$pagamento['id']
        ));

        $funcionario = trim((string)(
            $pagamento['funcionario_nome']
            ?? ''
        ));

        $descricao = 'Pagamento salarial';

        if ($funcionario !== '') {
            $descricao .= ' - ' . $funcionario;
        }

        $movimentoBancario = new MovimentoBancario($this->db);

        $movimentoBancario->registrar([
            'empresa_id' => $empresaId,
            'filial_id' =>
            !empty($pagamento['funcionario_filial_id'])
                ? (int)$pagamento['funcionario_filial_id']
                : null,
            'banco_id' => $bancoId,
            'usuario_id' => $usuarioId,
            'tipo' => 'SAIDA',
            'categoria' => 'PAGAMENTO',
            'origem' => 'PAGAMENTO_SALARIAL',
            'origem_id' => (int)$pagamento['id'],
            'descricao' => $descricao,
            'documento' => $numero,
            'referencia' =>
            'PAGAMENTO-SALARIAL-' . (int)$pagamento['id'],
            'valor' => round((float)$pagamento['valor_pago'], 2),
            'data_movimento' => $pagamento['data_pagamento'],
            'observacoes' => $pagamento['observacoes'] ?? null,
        ]);
    }

    /**
     * Cria o débito e o crédito do pagamento.
     */
    private function criarItensPagamento(
        int $lancamentoId,
        array $pagamento,
        array $contaSalariosPagar,
        array $contaPagamento
    ): void {
        $valorPago = round(
            (float)$pagamento['valor_pago'],
            2
        );

        $numero = trim(
            (string)(
                $pagamento['numero']
                ?? $pagamento['codigo']
                ?? $pagamento['id']
            )
        );

        /*
     * Débito — redução da obrigação salarial.
     */
        $this->inserirItemLancamento(
            $lancamentoId,
            (int)$contaSalariosPagar['id'],
            'Liquidação de salários a pagar - ' .
                $numero,
            $valorPago,
            0.00,
            1
        );

        /*
     * Crédito — saída do caixa ou banco.
     */
        $this->inserirItemLancamento(
            $lancamentoId,
            (int)$contaPagamento['id'],
            'Saída por pagamento salarial - ' .
                $numero,
            0.00,
            $valorPago,
            2
        );

        $this->validarItensLancamento(
            $lancamentoId,
            [
                'total_debito' => $valorPago,
                'total_credito' => $valorPago,
            ]
        );
    }

    /**
     * Guarda o lançamento contabilístico no pagamento salarial.
     */
    private function associarLancamentoPagamento(
        int $pagamentoId,
        int $empresaId,
        int $lancamentoId
    ): void {
        $sql = "
        UPDATE pagamentos_salariais
        SET lancamento_contabilistico_id =
            :lancamento_id
        WHERE id = :pagamento_id
          AND empresa_id = :empresa_id
          AND lancamento_contabilistico_id
              IS NULL
    ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':lancamento_id' => $lancamentoId,
            ':pagamento_id' => $pagamentoId,
            ':empresa_id' => $empresaId,
        ]);

        if ($stmt->rowCount() !== 1) {
            throw new RuntimeException(
                'Não foi possível associar o lançamento ao pagamento.'
            );
        }
    }

    /**
     * Obtém a conta utilizada no pagamento salarial.
     */
    private function obterContaPagamento(
        array $pagamento,
        int $empresaId,
        int $planoContasId
    ): array {
        $contaId = (int)(
            $pagamento['conta_contabil_id']
            ?? 0
        );

        /*
    |--------------------------------------------------------------------------
    | Se foi indicada uma conta específica
    |--------------------------------------------------------------------------
    */

        if ($contaId > 0) {

            $sql = "
            SELECT
                id,
                codigo,
                nome,
                integracao_codigo,
                tipo,
                natureza,
                grupo,
                aceita_lancamento,
                conta_caixa,
                conta_banco

            FROM contas_contabilisticas

            WHERE id = :conta_id
              AND empresa_id = :empresa_id
              AND plano_contas_id = :plano_contas_id

            LIMIT 1
        ";

            $stmt = $this->db->prepare($sql);

            $stmt->execute([
                ':conta_id' => $contaId,
                ':empresa_id' => $empresaId,
                ':plano_contas_id' =>
                $planoContasId,
            ]);

            $conta = $stmt->fetch(
                PDO::FETCH_ASSOC
            );

            if (!$conta) {
                throw new RuntimeException(
                    'A conta contabilística selecionada ' .
                        'não pertence ao plano de contas da empresa.'
                );
            }

            if (
                $conta['tipo'] !== 'ANALITICA' ||
                (int)$conta['aceita_lancamento'] !== 1
            ) {
                throw new RuntimeException(
                    'A conta contabilística selecionada ' .
                        'não permite lançamentos.'
                );
            }

            $formaPagamento = strtoupper(
                trim(
                    (string)$pagamento['forma_pagamento']
                )
            );

            /*
         * Numerário deve utilizar conta de Caixa.
         */
            if (
                $formaPagamento === 'NUMERARIO' &&
                (int)$conta['conta_caixa'] !== 1
            ) {
                throw new RuntimeException(
                    'Pagamentos em numerário devem utilizar ' .
                        'uma conta contabilística de Caixa.'
                );
            }

            /*
         * Pagamentos bancários devem utilizar conta Banco.
         */
            if (
                $formaPagamento !== 'NUMERARIO' &&
                (int)$conta['conta_banco'] !== 1
            ) {
                throw new RuntimeException(
                    'Esta forma de pagamento deve utilizar ' .
                        'uma conta contabilística bancária.'
                );
            }

            return $conta;
        }

        /*
    |--------------------------------------------------------------------------
    | Compatibilidade com pagamentos antigos
    |--------------------------------------------------------------------------
    |
    | Se o pagamento ainda não tiver conta_contabil_id,
    | tenta utilizar CAIXA ou BANCOS através do
    | integracao_codigo.
    |
    */

        $integracaoCodigo =
            $this->obterCodigoContaPagamento(
                (string)$pagamento['forma_pagamento']
            );

        return $this->obterContaPlano(
            $empresaId,
            $planoContasId,
            $integracaoCodigo
        );
    }

    /**
     * Valida se os itens gravados no lançamento
     * correspondem aos totais esperados.
     */
    private function validarItensLancamento(
        int $lancamentoId,
        array $totaisEsperados
    ): void {
        if ($lancamentoId <= 0) {
            throw new InvalidArgumentException(
                'O lançamento contabilístico é inválido.'
            );
        }

        $sql = "
        SELECT
            COALESCE(SUM(debito), 0) AS total_debito,
            COALESCE(SUM(credito), 0) AS total_credito
        FROM lancamento_contabilistico_itens
        WHERE lancamento_id = :lancamento_id
    ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':lancamento_id' => $lancamentoId,
        ]);

        $resultado = $stmt->fetch(
            PDO::FETCH_ASSOC
        );

        if (!$resultado) {
            throw new RuntimeException(
                'Não foi possível validar os itens do lançamento.'
            );
        }

        $totalDebito = round(
            (float)$resultado['total_debito'],
            2
        );

        $totalCredito = round(
            (float)$resultado['total_credito'],
            2
        );

        $debitoEsperado = round(
            (float)(
                $totaisEsperados['total_debito']
                ?? 0
            ),
            2
        );

        $creditoEsperado = round(
            (float)(
                $totaisEsperados['total_credito']
                ?? 0
            ),
            2
        );

        /*
    |--------------------------------------------------------------------------
    | Validar equilíbrio
    |--------------------------------------------------------------------------
    */

        if (
            abs(
                $totalDebito - $totalCredito
            ) > 0.01
        ) {
            throw new RuntimeException(
                'O lançamento contabilístico não está equilibrado. ' .
                    'Débito: ' .
                    number_format(
                        $totalDebito,
                        2,
                        ',',
                        '.'
                    ) .
                    ' | Crédito: ' .
                    number_format(
                        $totalCredito,
                        2,
                        ',',
                        '.'
                    )
            );
        }

        /*
    |--------------------------------------------------------------------------
    | Validar contra os totais esperados
    |--------------------------------------------------------------------------
    */

        if (
            abs(
                $totalDebito - $debitoEsperado
            ) > 0.01
            ||
            abs(
                $totalCredito - $creditoEsperado
            ) > 0.01
        ) {
            throw new RuntimeException(
                'Os totais dos itens do lançamento não correspondem ' .
                    'aos totais esperados. ' .
                    'Esperado: D ' .
                    number_format(
                        $debitoEsperado,
                        2,
                        ',',
                        '.'
                    ) .
                    ' / C ' .
                    number_format(
                        $creditoEsperado,
                        2,
                        ',',
                        '.'
                    ) .
                    '. Gravado: D ' .
                    number_format(
                        $totalDebito,
                        2,
                        ',',
                        '.'
                    ) .
                    ' / C ' .
                    number_format(
                        $totalCredito,
                        2,
                        ',',
                        '.'
                    )
            );
        }
    }
}
