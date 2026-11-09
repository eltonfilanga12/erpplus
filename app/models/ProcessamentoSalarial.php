<?php

declare(strict_types=1);

class ProcessamentoSalarial
{
    private PDO $db;

    private const TIPO_STATUS_PROCESSAMENTO =
    'processamento_salarial';

    private const TIPO_STATUS_PERIODO =
    'periodo_salarial';

    private const STATUS_PERIODO_ABERTO =
    'Aberto';

    private const STATUS_PERIODO_PROCESSAMENTO =
    'Em Processamento';

    private const STATUS_PERIODO_PROCESSADO =
    'Processado';

    private const STATUS_PROCESSAMENTO_PENDENTE =
    'Pendente';

    private const STATUS_PROCESSAMENTO_CALCULADO =
    'Calculado';

    private const STATUS_PROCESSAMENTO_APROVADO =
    'Aprovado';

    private const STATUS_PROCESSAMENTO_PAGO =
    'Pago';

    private const STATUS_PROCESSAMENTO_CANCELADO =
    'Cancelado';

    public function __construct(PDO $db)
    {
        $this->db = $db;

        $this->db->setAttribute(
            PDO::ATTR_ERRMODE,
            PDO::ERRMODE_EXCEPTION
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Listar processamentos
    |--------------------------------------------------------------------------
    */

    public function listar(
        int $empresaId,
        array $filtros = []
    ): array {
        if ($empresaId <= 0) {
            throw new Exception(
                'Empresa não informada.'
            );
        }

        $sql = "
            SELECT
                ps.id,
                ps.uuid,
                ps.empresa_id,
                ps.periodo_id,
                ps.funcionario_id,
                ps.contrato_id,
                ps.salario_funcionario_id,

                p.codigo AS periodo_codigo,
                p.descricao AS periodo,
                p.ano,
                p.mes,
                p.data_inicio AS periodo_inicio,
                p.data_fim AS periodo_fim,

                ps.numero_funcionario,
                ps.funcionario_nome,
                ps.cargo_nome,
                ps.departamento_nome,
                ps.filial_nome,

                ps.moeda,
                ps.salario_base,
                ps.total_salario_base,
                ps.total_subsidios,
                ps.total_horas_extras,
                ps.total_outros_proventos,
                ps.total_proventos,
                ps.total_faltas,
                ps.total_atrasos,
                ps.total_inss,
                ps.total_irps,
                ps.total_outros_descontos,
                ps.total_descontos,
                ps.salario_bruto,
                ps.salario_liquido,
                ps.valor_pago,
                ps.saldo_pendente,

                ps.dias_periodo,
                ps.dias_uteis,
                ps.dias_trabalhados,

                ps.processado_em,
                ps.aprovado_em,
                ps.cancelado_em,
                ps.versao,

                st.nome AS estado,

                ps.criado_em,
                ps.atualizado_em

            FROM processamentos_salariais ps

            INNER JOIN periodos_salariais p
                ON p.id = ps.periodo_id

            INNER JOIN status st
                ON st.id = ps.status_id

            WHERE ps.empresa_id = :empresa_id
        ";

        $params = [
            ':empresa_id' => $empresaId
        ];

        if (!empty($filtros['periodo_id'])) {
            $sql .= "
                AND ps.periodo_id = :periodo_id
            ";

            $params[':periodo_id'] =
                (int)$filtros['periodo_id'];
        }

        if (!empty($filtros['funcionario_id'])) {
            $sql .= "
                AND ps.funcionario_id =
                    :funcionario_id
            ";

            $params[':funcionario_id'] =
                (int)$filtros['funcionario_id'];
        }

        if (!empty($filtros['estado'])) {
            $sql .= "
                AND st.nome = :estado
            ";

            $params[':estado'] =
                trim(
                    (string)$filtros['estado']
                );
        }

        if (!empty($filtros['pesquisa'])) {
            $pesquisa =
                '%' .
                trim((string)$filtros['pesquisa'])
                . '%';

            $sql .= "
        AND (
            ps.funcionario_nome
                LIKE :pesquisa_nome

            OR ps.numero_funcionario
                LIKE :pesquisa_numero

            OR p.codigo
                LIKE :pesquisa_periodo
        )
    ";

            $params[':pesquisa_nome'] =
                $pesquisa;

            $params[':pesquisa_numero'] =
                $pesquisa;

            $params[':pesquisa_periodo'] =
                $pesquisa;
        }

        $sql .= "
            ORDER BY
                p.ano DESC,
                p.mes DESC,
                ps.funcionario_nome ASC
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute($params);

        return $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Buscar processamento
    |--------------------------------------------------------------------------
    */

    public function buscar(
        int $id,
        int $empresaId
    ): ?array {
        if ($id <= 0 || $empresaId <= 0) {
            return null;
        }

        $stmt = $this->db->prepare("
            SELECT
                ps.*,

                p.codigo AS periodo_codigo,
                p.descricao AS periodo_descricao,
                p.ano,
                p.mes,
                p.data_inicio AS periodo_inicio,
                p.data_fim AS periodo_fim,

                st.nome AS estado

            FROM processamentos_salariais ps

            INNER JOIN periodos_salariais p
                ON p.id = ps.periodo_id

            INNER JOIN status st
                ON st.id = ps.status_id

            WHERE ps.id = :id
              AND ps.empresa_id = :empresa_id

            LIMIT 1
        ");

        $stmt->execute([
            ':id' => $id,
            ':empresa_id' => $empresaId
        ]);

        $processamento = $stmt->fetch(
            PDO::FETCH_ASSOC
        );

        if (!$processamento) {
            return null;
        }

        $processamento['itens'] =
            $this->listarItens($id);

        return $processamento;
    }

    /*
    |--------------------------------------------------------------------------
    | Listar itens
    |--------------------------------------------------------------------------
    */

    public function listarItens(
        int $processamentoId
    ): array {
        $stmt = $this->db->prepare("
            SELECT
                id,
                processamento_id,
                rubrica_id,
                codigo_rubrica,
                nome_rubrica,
                descricao,
                tipo,
                categoria,
                origem,
                origem_registo_id,
                quantidade,
                valor_unitario,
                percentagem,
                base_calculo,
                valor_total,
                tributavel_inss,
                tributavel_irps,
                automatico,
                ordem,
                observacoes

            FROM processamento_salario_itens

            WHERE processamento_id =
                :processamento_id

            ORDER BY
                ordem ASC,
                tipo ASC,
                nome_rubrica ASC
        ");

        $stmt->execute([
            ':processamento_id' =>
            $processamentoId
        ]);

        return $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Processar período
    |--------------------------------------------------------------------------
    */

    public function processarPeriodo(
        int $empresaId,
        int $periodoId,
        int $utilizadorId
    ): array {
        if ($empresaId <= 0) {
            throw new Exception(
                'Empresa não informada.'
            );
        }

        if ($periodoId <= 0) {
            throw new Exception(
                'Período salarial não informado.'
            );
        }

        $periodo = $this->buscarPeriodo(
            $periodoId,
            $empresaId
        );

        if (!$periodo) {
            throw new Exception(
                'Período salarial não encontrado.'
            );
        }

        $estadosPermitidos = [
            self::STATUS_PERIODO_ABERTO,
            self::STATUS_PERIODO_PROCESSAMENTO
        ];


        if (
            !in_array(
                $periodo['estado'],
                $estadosPermitidos,
                true
            )
        ) {

            throw new Exception(
                'Apenas períodos com estado Aberto '
                    . 'ou Em Processamento podem ser processados.'
            );
        }

        $quantidadeExistente =
            $this->contarProcessamentosPeriodo(
                $periodoId,
                $empresaId
            );

        if ($quantidadeExistente > 0) {
            throw new Exception(
                'Este período já possui processamentos. '
                    . 'Utilize a opção de recálculo.'
            );
        }

        $funcionarios =
            $this->listarFuncionariosProcessaveis(
                $empresaId,
                (string)$periodo['data_inicio'],
                (string)$periodo['data_fim']
            );

        if (empty($funcionarios)) {
            throw new Exception(
                'Nenhum funcionário elegível foi '
                    . 'encontrado para este período.'
            );
        }

        $statusCalculado =
            $this->obterStatusId(
                self::TIPO_STATUS_PROCESSAMENTO,
                self::STATUS_PROCESSAMENTO_CALCULADO
            );

        try {
            $this->db->beginTransaction();

            $this->alterarStatusPeriodo(
                $periodoId,
                $empresaId,
                'Em Processamento'
            );

            $processados = 0;
            $totalBruto = 0.00;
            $totalDescontos = 0.00;
            $totalLiquido = 0.00;

            foreach ($funcionarios as $funcionario) {
                $resultado =
                    $this->processarFuncionario(
                        $empresaId,
                        $periodo,
                        $funcionario,
                        $statusCalculado,
                        $utilizadorId
                    );

                $processados++;

                $totalBruto +=
                    $resultado['salario_bruto'];

                $totalDescontos +=
                    $resultado['total_descontos'];

                $totalLiquido +=
                    $resultado['salario_liquido'];
            }

            $this->alterarStatusPeriodo(
                $periodoId,
                $empresaId,
                'Processado'
            );

            $this->db->commit();

            return [
                'success' => true,
                'message' =>
                'Período salarial processado com sucesso.',
                'periodo_id' => $periodoId,
                'quantidade_funcionarios' =>
                $processados,
                'total_bruto' =>
                round($totalBruto, 2),
                'total_descontos' =>
                round($totalDescontos, 2),
                'total_liquido' =>
                round($totalLiquido, 2)
            ];
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            throw $e;
        }
    }


    public function recalcularPeriodo(
        int $empresaId,
        int $periodoId,
        int $utilizadorId
    ): array {

        if ($empresaId <= 0) {
            throw new Exception(
                'Empresa não informada.'
            );
        }

        if ($periodoId <= 0) {
            throw new Exception(
                'Período salarial não informado.'
            );
        }

        $periodo =
            $this->buscarPeriodo(
                $periodoId,
                $empresaId
            );

        if (!$periodo) {
            throw new Exception(
                'Período salarial não encontrado.'
            );
        }

        /*
     * Não permitir recálculo se já existir
     * processamento pago.
     */
        $stmtPago =
            $this->db->prepare("
            SELECT COUNT(*)

            FROM processamentos_salariais ps

            INNER JOIN status st
                ON st.id = ps.status_id
               AND st.tipo =
                    'processamento_salarial'

            WHERE ps.empresa_id =
                :empresa_id

              AND ps.periodo_id =
                :periodo_id

              AND st.nome =
                'Pago'
        ");

        $stmtPago->execute([
            ':empresa_id' =>
            $empresaId,

            ':periodo_id' =>
            $periodoId
        ]);

        if (
            (int)$stmtPago->fetchColumn() > 0
        ) {
            throw new Exception(
                'Não é possível recalcular o período '
                    . 'porque existem salários já pagos.'
            );
        }

        /*
|--------------------------------------------------------------------------
| Verificar documentos financeiros vinculados
|--------------------------------------------------------------------------
|
| Um processamento que já gerou pagamento ou recibo não deve ser
| destruído/recriado, pois esses documentos referenciam o ID original.
|
*/

        $stmtVinculos = $this->db->prepare("
    SELECT
        COUNT(DISTINCT ps.id) AS total

    FROM processamentos_salariais ps

    WHERE ps.empresa_id = :empresa_id
      AND ps.periodo_id = :periodo_id

      AND (
            EXISTS (
                SELECT 1
                FROM pagamentos_salariais pg
                WHERE pg.processamento_id = ps.id
            )

            OR EXISTS (
                SELECT 1
                FROM recibos_salarios rs
                WHERE rs.processamento_id = ps.id
            )
      )
");

        $stmtVinculos->execute([
            ':empresa_id' => $empresaId,
            ':periodo_id' => $periodoId
        ]);

        $totalVinculados =
            (int)$stmtVinculos->fetchColumn();

        if ($totalVinculados > 0) {
            throw new Exception(
                'Não é possível recalcular este período porque existem '
                    . 'processamentos com pagamentos ou recibos emitidos.'
            );
        }

        $funcionarios =
            $this->listarFuncionariosProcessaveis(
                $empresaId,
                (string)$periodo['data_inicio'],
                (string)$periodo['data_fim']
            );

        if (empty($funcionarios)) {
            throw new Exception(
                'Nenhum funcionário elegível foi '
                    . 'encontrado para este período.'
            );
        }



        $statusCalculado =
            $this->obterStatusId(
                self::TIPO_STATUS_PROCESSAMENTO,
                self::STATUS_PROCESSAMENTO_CALCULADO
            );

        try {

            $this->db->beginTransaction();

            /*
         * Primeiro apagar os itens antigos.
         */
            $stmtItens =
                $this->db->prepare("
                DELETE psi

                FROM processamento_salario_itens psi

                INNER JOIN processamentos_salariais ps
                    ON ps.id =
                        psi.processamento_id

                WHERE ps.empresa_id =
                    :empresa_id

                  AND ps.periodo_id =
                    :periodo_id
            ");

            $stmtItens->execute([
                ':empresa_id' =>
                $empresaId,

                ':periodo_id' =>
                $periodoId
            ]);

            /*
         * Depois apagar os processamentos antigos.
         */
            $stmtProcessamentos =
                $this->db->prepare("
                DELETE FROM processamentos_salariais

                WHERE empresa_id =
                    :empresa_id

                  AND periodo_id =
                    :periodo_id
            ");

            $stmtProcessamentos->execute([
                ':empresa_id' =>
                $empresaId,

                ':periodo_id' =>
                $periodoId
            ]);

            /*
         * Colocar o período novamente
         * em processamento.
         */
            $this->alterarStatusPeriodo(
                $periodoId,
                $empresaId,
                self::STATUS_PERIODO_PROCESSAMENTO
            );

            $processados = 0;
            $totalBruto = 0.00;
            $totalDescontos = 0.00;
            $totalLiquido = 0.00;

            foreach (
                $funcionarios as $funcionario
            ) {

                $resultado =
                    $this->processarFuncionario(
                        $empresaId,
                        $periodo,
                        $funcionario,
                        $statusCalculado,
                        $utilizadorId
                    );

                $processados++;

                $totalBruto +=
                    (float)$resultado['salario_bruto'];

                $totalDescontos +=
                    (float)$resultado['total_descontos'];

                $totalLiquido +=
                    (float)$resultado['salario_liquido'];
            }

            $this->alterarStatusPeriodo(
                $periodoId,
                $empresaId,
                self::STATUS_PERIODO_PROCESSADO
            );

            $this->db->commit();

            return [
                'success' =>
                true,

                'message' =>
                'Período salarial recalculado com sucesso.',

                'periodo_id' =>
                $periodoId,

                'quantidade_funcionarios' =>
                $processados,

                'total_bruto' =>
                round(
                    $totalBruto,
                    2
                ),

                'total_descontos' =>
                round(
                    $totalDescontos,
                    2
                ),

                'total_liquido' =>
                round(
                    $totalLiquido,
                    2
                )
            ];
        } catch (Throwable $e) {

            if (
                $this->db->inTransaction()
            ) {
                $this->db->rollBack();
            }

            throw $e;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Processar funcionário
    |--------------------------------------------------------------------------
    */

    private function processarFuncionario(
        int $empresaId,
        array $periodo,
        array $funcionario,
        int $statusCalculado,
        int $utilizadorId
    ): array {
        $salario = $this->buscarSalarioVigente(
            $empresaId,
            (int) $funcionario['funcionario_id'],
            $periodo['data_inicio'],
            $periodo['data_fim']
        );
        if (!$salario) {
            throw new Exception(
                'O funcionário '
                    . $funcionario['funcionario_nome']
                    . ' não possui configuração salarial '
                    . 'activa para o período.'
            );
        }

        $salarioBase =
            round(
                (float)$salario['salario_base'],
                2
            );

        $diasMensais =
            (float)$salario['dias_mensais'];

        if ($diasMensais <= 0) {
            $diasMensais =
                (float)$periodo['dias_uteis'];
        }

        if ($diasMensais <= 0) {
            $diasMensais = 30;
        }

        $horasMensais =
            (float)$salario['horas_mensais'];

        if ($horasMensais <= 0) {
            $horasMensais = 176;
        }

        $diasPeriodo =
            (float)$periodo['dias_calendario'];

        $diasUteis =
            (float)$periodo['dias_uteis'];

        /*
|--------------------------------------------------------------------------
| Integração com Assiduidade
|--------------------------------------------------------------------------
*/

        $funcionarioId =
            (int)$funcionario['funcionario_id'];

        $assiduidade =
            $this->buscarResumoAssiduidadeProcessamento(
                $empresaId,
                $funcionarioId,
                (string)$periodo['data_inicio'],
                (string)$periodo['data_fim']
            );


        $diasTrabalhados =
            (float)(
                $assiduidade['dias_trabalhados']
                ?? 0
            );


        $valorDia =
            round(
                $salarioBase / $diasMensais,
                6
            );


        /*
 * Não arredondamos excessivamente antes
 * dos cálculos finais.
 */
        $valorHora =
            $salarioBase / $horasMensais;


        $valorMinuto =
            $valorHora / 60;


        /*
        * O salário base contratual continua integral.
        *
        * Faltas e atrasos entram como descontos separados.
        * Isso deixa a folha mais auditável:
        *
        * Salário base       15.000
        * (-) atraso             42
        * (-) falta               X
        */
        $totalSalarioBase =
            $salarioBase;

        $processamentoId =
            $this->inserirProcessamento(
                [
                    'empresa_id' =>
                    $empresaId,

                    'periodo_id' =>
                    (int)$periodo['id'],

                    'funcionario_id' =>
                    (int)$funcionario['funcionario_id'],

                    'contrato_id' =>
                    !empty($funcionario['contrato_id'])
                        ? (int)$funcionario['contrato_id']
                        : null,

                    'salario_funcionario_id' =>
                    (int)$salario['id'],

                    'moeda' =>
                    $salario['moeda'] ?: 'MZN',

                    'numero_funcionario' =>
                    $funcionario['numero_funcionario'],

                    'funcionario_nome' =>
                    $funcionario['funcionario_nome'],

                    'cargo_nome' =>
                    $funcionario['cargo_nome'],

                    'departamento_nome' =>
                    $funcionario['departamento_nome'],

                    'filial_nome' =>
                    $funcionario['filial_nome'],

                    'dias_periodo' =>
                    $diasPeriodo,

                    'dias_uteis' =>
                    $diasUteis,

                    'dias_trabalhados' =>
                    $diasTrabalhados,

                    'salario_base' =>
                    $salarioBase,

                    'valor_dia' =>
                    $valorDia,

                    'valor_hora' =>
                    $valorHora,

                    'total_salario_base' =>
                    $totalSalarioBase,

                    'status_id' =>
                    $statusCalculado,

                    'utilizador_id' =>
                    $utilizadorId
                ]
            );

        /*
         * Item do salário base.
         */
        $this->inserirItem(
            [
                'processamento_id' =>
                $processamentoId,

                'rubrica_id' =>
                null,

                'codigo_rubrica' =>
                'SALARIO_BASE',

                'nome_rubrica' =>
                'Salário Base',

                'descricao' =>
                'Salário base do período',

                'tipo' =>
                'PROVENTO',

                'categoria' =>
                'SALARIO_BASE',

                'origem' =>
                'SALARIO',

                'origem_registo_id' =>
                (int)$salario['id'],

                'quantidade' =>
                1,

                'valor_unitario' =>
                $totalSalarioBase,

                'percentagem' =>
                0,

                'base_calculo' =>
                $salarioBase,

                'valor_total' =>
                $totalSalarioBase,

                'tributavel_inss' =>
                1,

                'tributavel_irps' =>
                1,

                'automatico' =>
                1,

                'ordem' =>
                1,

                'observacoes' =>
                null,

                'criado_por' =>
                $utilizadorId
            ]
        );

        $rubricas =
            $this->listarRubricasSalario(
                (int)$salario['id']
            );

        $totalSubsidios = 0.00;
        $totalHorasExtras = 0.00;
        $totalOutrosProventos = 0.00;

        $totalFaltas = 0.00;
        $totalAtrasos = 0.00;

        $totalInss = 0.00;
        $totalInssPatronal = 0.00;

        $totalIrps = 0.00;

        $totalOutrosDescontos = 0.00;

        $totalProventosRubricas = 0.00;
        $totalDescontos = 0.00;

        $ordem = 10;

        foreach ($rubricas as $rubrica) {
            /*
             * O salário base já foi incluído.
             */
            /*
            * Estas categorias serão alimentadas
            * automaticamente pela Assiduidade.
            *
            * Não podem ser processadas novamente
            * como rubricas fixas.
            */
            if (
                in_array(
                    strtoupper(
                        (string)$rubrica['categoria']
                    ),
                    [
                        'SALARIO_BASE',
                        'HORA_EXTRA',
                        'FALTA',
                        'ATRASO',
                        'INSS',
                        'INSS_PATRONAL',
                        'IRPS'
                    ],
                    true
                )
            ) {
                continue;
            }

            $calculo =
                $this->calcularRubrica(
                    $rubrica,
                    [
                        'salario_base' =>
                        $salarioBase,

                        'total_proventos' =>
                        $totalSalarioBase
                            + $totalProventosRubricas,

                        'salario_bruto' =>
                        $totalSalarioBase
                            + $totalProventosRubricas,

                        'valor_hora' =>
                        $valorHora,

                        'valor_dia' =>
                        $valorDia
                    ]
                );

            $valorTotal =
                round(
                    $calculo['valor_total'],
                    2
                );

            if ($valorTotal == 0.00) {
                continue;
            }

            $this->inserirItem(
                [
                    'processamento_id' =>
                    $processamentoId,

                    'rubrica_id' =>
                    (int)$rubrica['rubrica_id'],

                    'codigo_rubrica' =>
                    $rubrica['codigo'],

                    'nome_rubrica' =>
                    $rubrica['nome'],

                    'descricao' =>
                    $rubrica['descricao'],

                    'tipo' =>
                    $rubrica['tipo'],

                    'categoria' =>
                    $rubrica['categoria'],

                    'origem' =>
                    'RUBRICA_FIXA',

                    'origem_registo_id' =>
                    (int)$rubrica['salario_rubrica_id'],

                    'quantidade' =>
                    $calculo['quantidade'],

                    'valor_unitario' =>
                    $calculo['valor_unitario'],

                    'percentagem' =>
                    $calculo['percentagem'],

                    'base_calculo' =>
                    $calculo['base_calculo'],

                    'valor_total' =>
                    $valorTotal,

                    'tributavel_inss' =>
                    (int)$rubrica['tributavel_inss'],

                    'tributavel_irps' =>
                    (int)$rubrica['tributavel_irps'],

                    'automatico' =>
                    1,

                    'ordem' =>
                    $ordem,

                    'observacoes' =>
                    $rubrica['observacoes'],

                    'criado_por' =>
                    $utilizadorId
                ]
            );

            $ordem += 10;

            if ($rubrica['tipo'] === 'PROVENTO') {
                $totalProventosRubricas +=
                    $valorTotal;

                if (
                    str_starts_with(
                        $rubrica['categoria'],
                        'SUBSIDIO_'
                    )
                ) {
                    $totalSubsidios +=
                        $valorTotal;
                } elseif (
                    $rubrica['categoria'] ===
                    'HORA_EXTRA'
                ) {
                    $totalHorasExtras +=
                        $valorTotal;
                } else {
                    $totalOutrosProventos +=
                        $valorTotal;
                }
            }

            if ($rubrica['tipo'] === 'DESCONTO') {
                $totalDescontos +=
                    $valorTotal;

                switch ($rubrica['categoria']) {
                    case 'FALTA':
                        $totalFaltas +=
                            $valorTotal;
                        break;

                    case 'ATRASO':
                        $totalAtrasos +=
                            $valorTotal;
                        break;

                    case 'INSS':
                        $totalInss +=
                            $valorTotal;
                        break;

                    case 'IRPS':
                        $totalIrps +=
                            $valorTotal;
                        break;

                    default:
                        $totalOutrosDescontos +=
                            $valorTotal;
                        break;
                }
            }
        }

        /*
        |--------------------------------------------------------------------------
        | HORAS EXTRAS APROVADAS
        |--------------------------------------------------------------------------
        */

        $horasExtras =
            $this->listarHorasExtrasProcessamento(
                $empresaId,
                $funcionarioId,
                (string)$periodo['data_inicio'],
                (string)$periodo['data_fim']
            );


        if (!empty($horasExtras)) {

            $rubricaHoraExtra =
                $this->buscarRubricaAutomatica(
                    $empresaId,
                    'HORA_EXTRA'
                );


            if (!$rubricaHoraExtra) {

                throw new RuntimeException(
                    'A rubrica HE_NORMAL não está configurada '
                        . 'ou não se encontra activa.'
                );
            }


            foreach (
                $horasExtras as $horaExtra
            ) {

                $minutos =
                    (int)(
                        $horaExtra['minutos_aprovados']
                        ?? 0
                    );


                $valorTotal =
                    round(
                        (float)(
                            $horaExtra['valor_total']
                            ?? 0
                        ),
                        2
                    );


                if (
                    $minutos <= 0
                    || $valorTotal <= 0
                ) {
                    continue;
                }


                $quantidadeHoras =
                    $minutos / 60;


                $this->inserirItem([
                    'processamento_id' =>
                    $processamentoId,

                    'rubrica_id' =>
                    (int)$rubricaHoraExtra['id'],

                    'codigo_rubrica' =>
                    $rubricaHoraExtra['codigo'],

                    'nome_rubrica' =>
                    $rubricaHoraExtra['nome'],

                    'descricao' =>
                    'Hora extra aprovada pela assiduidade',

                    'tipo' =>
                    'PROVENTO',

                    'categoria' =>
                    'HORA_EXTRA',

                    'origem' =>
                    'HORA_EXTRA',

                    'origem_registo_id' =>
                    (int)$horaExtra['id'],

                    'quantidade' =>
                    round(
                        $quantidadeHoras,
                        6
                    ),

                    'valor_unitario' =>
                    (float)(
                        $horaExtra['valor_hora']
                        ?? $valorHora
                    ),

                    'percentagem' =>
                    (float)(
                        $horaExtra['percentual_acrescimo']
                        ?? 0
                    ),

                    'base_calculo' =>
                    $salarioBase,

                    'valor_total' =>
                    $valorTotal,

                    'tributavel_inss' =>
                    (int)$rubricaHoraExtra['tributavel_inss'],

                    'tributavel_irps' =>
                    (int)$rubricaHoraExtra['tributavel_irps'],

                    'automatico' =>
                    1,

                    'ordem' =>
                    $ordem,

                    'observacoes' =>
                    'Gerado automaticamente '
                        . 'a partir da hora extra #'
                        . (int)$horaExtra['id'],

                    'criado_por' =>
                    $utilizadorId
                ]);


                $ordem += 10;

                $totalHorasExtras +=
                    $valorTotal;

                $totalProventosRubricas +=
                    $valorTotal;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | ATRASOS
        |--------------------------------------------------------------------------
        */

        $minutosAtraso =
            (int)(
                $assiduidade['minutos_atraso']
                ?? 0
            );

        if ($minutosAtraso > 0) {

            $rubricaAtraso =
                $this->buscarRubricaAutomatica(
                    $empresaId,
                    'ATRASO'
                );


            if (!$rubricaAtraso) {

                throw new RuntimeException(
                    'A rubrica ATRASO não está configurada '
                        . 'ou não se encontra activa.'
                );
            }


            $valorAtraso =
                round(
                    $minutosAtraso
                        * $valorMinuto,
                    2
                );


            if ($valorAtraso > 0) {

                $this->inserirItem([
                    'processamento_id' =>
                    $processamentoId,

                    'rubrica_id' =>
                    (int)$rubricaAtraso['id'],

                    'codigo_rubrica' =>
                    $rubricaAtraso['codigo'],

                    'nome_rubrica' =>
                    $rubricaAtraso['nome'],

                    'descricao' =>
                    'Desconto automático por atraso',

                    'tipo' =>
                    'DESCONTO',

                    'categoria' =>
                    'ATRASO',

                    'origem' =>
                    'ASSIDUIDADE',

                    'origem_registo_id' =>
                    null,

                    'quantidade' =>
                    $minutosAtraso,

                    'valor_unitario' =>
                    round(
                        $valorMinuto,
                        6
                    ),

                    'percentagem' =>
                    0,

                    'base_calculo' =>
                    $salarioBase,

                    'valor_total' =>
                    $valorAtraso,

                    'tributavel_inss' =>
                    0,

                    'tributavel_irps' =>
                    0,

                    'automatico' =>
                    1,

                    'ordem' =>
                    $ordem,

                    'observacoes' =>
                    $minutosAtraso
                        . ' minuto(s) de atraso no período.',

                    'criado_por' =>
                    $utilizadorId
                ]);


                $ordem += 10;

                $totalAtrasos +=
                    $valorAtraso;

                $totalDescontos +=
                    $valorAtraso;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | FALTAS DESCONTÁVEIS
        |--------------------------------------------------------------------------
        */

        $faltas =
            $this->listarFaltasProcessamento(
                $empresaId,
                $funcionarioId,
                (string)$periodo['data_inicio'],
                (string)$periodo['data_fim']
            );


        if (!empty($faltas)) {

            $rubricaFalta =
                $this->buscarRubricaAutomatica(
                    $empresaId,
                    'FALTA'
                );


            if (!$rubricaFalta) {

                throw new RuntimeException(
                    'A rubrica FALTA não está configurada '
                        . 'ou não se encontra activa.'
                );
            }


            foreach ($faltas as $falta) {

                /*
                * minutos_falta pode já conter atraso.
                *
                * Exemplo:
                *
                * entrada 08:30
                * atraso = 30
                * minutos_falta = 30
                *
                * Não podemos descontar:
                * atraso 30 + falta 30.
                *
                * Portanto retiramos da falta os
                * minutos que já foram classificados
                * como atraso no mesmo dia.
                */
                $minutosFalta =
                    max(
                        0,
                        (int)(
                            $falta['minutos_falta']
                            ?? 0
                        )
                            -
                            (int)(
                                $falta['minutos_atraso']
                                ?? 0
                            )
                    );


                if ($minutosFalta <= 0) {
                    continue;
                }


                $valorFalta =
                    round(
                        $minutosFalta
                            * $valorMinuto,
                        2
                    );


                if ($valorFalta <= 0) {
                    continue;
                }


                $this->inserirItem([
                    'processamento_id' =>
                    $processamentoId,

                    'rubrica_id' =>
                    (int)$rubricaFalta['id'],

                    'codigo_rubrica' =>
                    $rubricaFalta['codigo'],

                    'nome_rubrica' =>
                    $rubricaFalta['nome'],

                    'descricao' =>
                    'Desconto automático por falta',

                    'tipo' =>
                    'DESCONTO',

                    'categoria' =>
                    'FALTA',

                    'origem' =>
                    'FALTA',

                    'origem_registo_id' =>
                    (int)$falta['id'],

                    'quantidade' =>
                    $minutosFalta,

                    'valor_unitario' =>
                    round(
                        $valorMinuto,
                        6
                    ),

                    'percentagem' =>
                    0,

                    'base_calculo' =>
                    $salarioBase,

                    'valor_total' =>
                    $valorFalta,

                    'tributavel_inss' =>
                    0,

                    'tributavel_irps' =>
                    0,

                    'automatico' =>
                    1,

                    'ordem' =>
                    $ordem,

                    'observacoes' =>
                    $minutosFalta
                        . ' minuto(s) descontáveis.',

                    'criado_por' =>
                    $utilizadorId
                ]);


                $ordem += 10;

                $totalFaltas +=
                    $valorFalta;

                $totalDescontos +=
                    $valorFalta;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | INSS
        |--------------------------------------------------------------------------
        |
        | Trabalhador por conta de outrem:
        |
        | Trabalhador: 3%
        | Empregador : 4%
        |
        | A incidência é feita apenas sobre itens marcados como
        | tributavel_inss = 1.
        |--------------------------------------------------------------------------
        */

        $baseInss =
            $this->calcularBaseInss(
                $processamentoId
            );


        /*
        |--------------------------------------------------------------------------
        | INSS do trabalhador
        |--------------------------------------------------------------------------
        */

        $totalInss =
            round(
                $baseInss * 0.03,
                2
            );


        /*
        |--------------------------------------------------------------------------
        | INSS patronal
        |--------------------------------------------------------------------------
        */

        $totalInssPatronal =
            round(
                $baseInss * 0.04,
                2
            );


        /*
        |--------------------------------------------------------------------------
        | Rubrica de INSS trabalhador
        |--------------------------------------------------------------------------
        */



        if ($totalInss > 0) {

            $rubricaInss =
                $this->buscarRubricaAutomatica(
                    $empresaId,
                    'INSS'
                );

            if (!$rubricaInss) {
                throw new RuntimeException(
                    'A rubrica da categoria INSS não está configurada '
                        . 'ou não se encontra activa.'
                );
            }

            $this->inserirItem([
                'processamento_id' =>
                $processamentoId,

                'rubrica_id' =>
                (int)$rubricaInss['id'],

                'codigo_rubrica' =>
                $rubricaInss['codigo'],

                'nome_rubrica' =>
                $rubricaInss['nome'],

                'descricao' =>
                'Contribuição do trabalhador para o INSS',

                'tipo' =>
                'DESCONTO',

                'categoria' =>
                'INSS',

                'origem' =>
                'INSS',

                'origem_registo_id' =>
                null,

                'quantidade' =>
                1,

                'valor_unitario' =>
                $totalInss,

                'percentagem' =>
                3.00,

                'base_calculo' =>
                $baseInss,

                'valor_total' =>
                $totalInss,

                'tributavel_inss' =>
                0,

                'tributavel_irps' =>
                0,

                'automatico' =>
                1,

                'ordem' =>
                $ordem,

                'observacoes' =>
                'Calculado automaticamente à taxa de 3%.',

                'criado_por' =>
                $utilizadorId
            ]);

            $ordem += 10;

            $totalDescontos +=
                $totalInss;
        }


        /*
        |--------------------------------------------------------------------------
        | INSS Patronal
        |--------------------------------------------------------------------------
        |
        | O INSS patronal NÃO é desconto do trabalhador.
        |
        | Portanto:
        | - não entra em $totalDescontos;
        | - não reduz $salarioLiquido;
        | - fica registado separadamente como encargo da empresa.
        |--------------------------------------------------------------------------
        */


        if ($totalInssPatronal > 0) {


            $rubricaInssPatronal =
                $this->buscarRubricaAutomatica(
                    $empresaId,
                    'INSS_PATRONAL'
                );

            if (!$rubricaInssPatronal) {
                throw new RuntimeException(
                    'A rubrica da categoria INSS_PATRONAL não está configurada '
                        . 'ou não se encontra activa.'
                );
            }

            $this->inserirItem([
                'processamento_id' =>
                $processamentoId,

                'rubrica_id' =>
                (int)$rubricaInssPatronal['id'],

                'codigo_rubrica' =>
                $rubricaInssPatronal['codigo'],

                'nome_rubrica' =>
                $rubricaInssPatronal['nome'],

                'descricao' =>
                'Contribuição patronal para o INSS',

                /*
                | Não usamos DESCONTO porque não é
                | desconto do salário do funcionário.
                */
                'tipo' =>
                'ENCARGO',

                'categoria' =>
                'INSS_PATRONAL',

                'origem' =>
                'INSS',

                'origem_registo_id' =>
                null,

                'quantidade' =>
                1,

                'valor_unitario' =>
                $totalInssPatronal,

                'percentagem' =>
                4.00,

                'base_calculo' =>
                $baseInss,

                'valor_total' =>
                $totalInssPatronal,

                'tributavel_inss' =>
                0,

                'tributavel_irps' =>
                0,

                'automatico' =>
                1,

                'ordem' =>
                $ordem,

                'observacoes' =>
                'Calculado automaticamente à taxa patronal de 4%.',

                'criado_por' =>
                $utilizadorId
            ]);


            $ordem += 10;
        }

        /*
        |--------------------------------------------------------------------------
        | IRPS AUTOMÁTICO
        |--------------------------------------------------------------------------
        */



        $calculoIrps =
            $this->calcularIrps(
                $funcionarioId,
                $processamentoId,
                $periodo
            );


        $totalIrps =
            round(
                (float)$calculoIrps['valor'],
                2
            );


        if ($totalIrps > 0) {

            $rubricaIrps =
                $this->buscarRubricaAutomatica(
                    $empresaId,
                    'IRPS'
                );

            if (!$rubricaIrps) {
                throw new RuntimeException(
                    'A rubrica da categoria IRPS não está configurada '
                        . 'ou não se encontra activa.'
                );
            }

            $this->inserirItem([
                'processamento_id' =>
                $processamentoId,

                'rubrica_id' =>
                (int)$rubricaIrps['id'],

                'codigo_rubrica' =>
                $rubricaIrps['codigo'],

                'nome_rubrica' =>
                $rubricaIrps['nome'],

                'descricao' =>
                'Retenção na fonte do IRPS',

                'tipo' =>
                'DESCONTO',

                'categoria' =>
                'IRPS',

                'origem' =>
                'IRPS',

                'origem_registo_id' =>
                $calculoIrps['escalao_id'],

                'quantidade' =>
                1,

                'valor_unitario' =>
                $totalIrps,

                /*
                |--------------------------------------------------------------------------
                | Aqui guardamos o coeficiente percentual
                |--------------------------------------------------------------------------
                */

                'percentagem' =>
                $calculoIrps['percentagem'],

                'base_calculo' =>
                $calculoIrps['base'],

                'valor_total' =>
                $totalIrps,

                'tributavel_inss' =>
                0,

                'tributavel_irps' =>
                0,

                'automatico' =>
                1,

                'ordem' =>
                $ordem,

                'observacoes' =>
                'IRPS calculado automaticamente. '
                    . 'Dependentes: '
                    . $calculoIrps['dependentes']
                    . '.',

                'criado_por' =>
                $utilizadorId
            ]);


            $ordem += 10;


            /*
            |--------------------------------------------------------------------------
            | IRPS reduz o salário líquido
            |--------------------------------------------------------------------------
            */

            $totalDescontos +=
                $totalIrps;
        }


        $totalProventos =
            round(
                $totalSalarioBase
                    + $totalProventosRubricas,
                2
            );

        $salarioBruto =
            $totalProventos;

        $salarioLiquido =
            round(
                $salarioBruto
                    - $totalDescontos,
                2
            );

        $saldoPendente =
            max($salarioLiquido, 0);

        $this->atualizarTotaisProcessamento(
            $processamentoId,
            $empresaId,
            [
                'total_subsidios' =>
                $totalSubsidios,

                'total_horas_extras' =>
                $totalHorasExtras,

                'total_outros_proventos' =>
                $totalOutrosProventos,

                'total_proventos' =>
                $totalProventos,

                'total_faltas' =>
                $totalFaltas,

                'total_atrasos' =>
                $totalAtrasos,

                'total_inss' =>
                $totalInss,

                'total_inss_patronal' =>
                $totalInssPatronal,

                'total_irps' =>
                $totalIrps,

                'total_outros_descontos' =>
                $totalOutrosDescontos,

                'total_descontos' =>
                $totalDescontos,

                'salario_bruto' =>
                $salarioBruto,

                'salario_liquido' =>
                $salarioLiquido,

                'saldo_pendente' =>
                $saldoPendente,

                'utilizador_id' =>
                $utilizadorId
            ]
        );

        return [
            'processamento_id' =>
            $processamentoId,

            'salario_bruto' =>
            $salarioBruto,

            'total_descontos' =>
            $totalDescontos,

            'salario_liquido' =>
            $salarioLiquido
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Calcular rubrica
    |--------------------------------------------------------------------------
    */

    private function calcularRubrica(
        array $rubrica,
        array $bases
    ): array {
        $modo =
            strtoupper(
                (string)$rubrica['modo_calculo']
            );

        $baseCalculo =
            $this->resolverBaseCalculo(
                (string)$rubrica['base_calculo'],
                $bases
            );

        $valorConfigurado =
            (float)($rubrica['valor'] ?? 0);

        $valorPadrao =
            (float)($rubrica['valor_padrao'] ?? 0);

        $percentagemConfigurada =
            (float)($rubrica['percentagem'] ?? 0);

        $percentagemPadrao =
            (float)(
                $rubrica['percentagem_padrao'] ?? 0
            );

        $quantidade =
            (float)(
                $rubrica['quantidade_padrao']
                ?? 1
            );

        if ($quantidade <= 0) {
            $quantidade = 1;
        }
        $valorUnitario = 0.00;
        $percentagem = 0.00;
        $valorTotal = 0.00;

        switch ($modo) {
            case 'VALOR_FIXO':
                $valorUnitario =
                    $valorConfigurado > 0
                    ? $valorConfigurado
                    : $valorPadrao;

                $quantidade = 1;

                $valorTotal =
                    $valorUnitario;
                break;

            case 'PERCENTAGEM':
                $percentagem =
                    $percentagemConfigurada > 0
                    ? $percentagemConfigurada
                    : $percentagemPadrao;

                $valorTotal =
                    (
                        $baseCalculo
                        * $percentagem
                    ) / 100;

                $valorUnitario =
                    $valorTotal;

                $quantidade = 1;
                break;

            case 'QUANTIDADE_VALOR':
                $valorUnitario =
                    $valorConfigurado > 0
                    ? $valorConfigurado
                    : $valorPadrao;

                if ($quantidade <= 0) {
                    $quantidade = 1;
                }

                $valorTotal =
                    $quantidade
                    * $valorUnitario;
                break;

            case 'MANUAL':
                $valorUnitario =
                    $valorConfigurado;

                $quantidade = 1;

                $valorTotal =
                    $valorConfigurado;
                break;

            case 'FORMULA':
                /*
                 * Não usamos eval() por segurança.
                 * Até ser criado um interpretador de fórmulas,
                 * utiliza-se o valor configurado.
                 */
                $valorUnitario =
                    $valorConfigurado > 0
                    ? $valorConfigurado
                    : $valorPadrao;

                $quantidade = 1;

                $valorTotal =
                    $valorUnitario;
                break;

            default:
                $valorUnitario =
                    $valorConfigurado > 0
                    ? $valorConfigurado
                    : $valorPadrao;

                $quantidade = 1;

                $valorTotal =
                    $valorUnitario;
                break;
        }

        return [
            'quantidade' =>
            round($quantidade, 6),

            'valor_unitario' =>
            round($valorUnitario, 6),

            'percentagem' =>
            round($percentagem, 4),

            'base_calculo' =>
            round($baseCalculo, 2),

            'valor_total' =>
            round($valorTotal, 2)
        ];
    }

    private function resolverBaseCalculo(
        string $tipo,
        array $bases
    ): float {
        return match (strtoupper(trim($tipo))) {
            'SALARIO_BASE' =>
            (float)$bases['salario_base'],

            'TOTAL_PROVENTOS' =>
            (float)$bases['total_proventos'],

            'SALARIO_BRUTO' =>
            (float)$bases['salario_bruto'],

            'VALOR_HORA' =>
            (float)$bases['valor_hora'],

            'VALOR_DIA' =>
            (float)$bases['valor_dia'],

            default => 0.00
        };
    }

    /*
    |--------------------------------------------------------------------------
    | Aprovar
    |--------------------------------------------------------------------------
    */

    public function aprovar(
        int $id,
        int $empresaId,
        int $utilizadorId
    ): array {
        $processamento =
            $this->buscar($id, $empresaId);

        if (!$processamento) {
            throw new Exception(
                'Processamento salarial não encontrado.'
            );
        }

        if (
            $processamento['estado'] !==
            self::STATUS_PROCESSAMENTO_CALCULADO
        ) {
            throw new Exception(
                'Apenas processamentos calculados '
                    . 'podem ser aprovados.'
            );
        }

        $statusId =
            $this->obterStatusId(
                self::TIPO_STATUS_PROCESSAMENTO,
                self::STATUS_PROCESSAMENTO_APROVADO
            );

        $stmt = $this->db->prepare("
            UPDATE processamentos_salariais

            SET
                status_id = :status_id,
                aprovado_por = :aprovado_por,
                aprovado_em = NOW(),
                atualizado_por = :atualizado_por

            WHERE id = :id
              AND empresa_id = :empresa_id

            LIMIT 1
        ");

        $stmt->execute([
            ':status_id' => $statusId,
            ':aprovado_por' =>
            $utilizadorId > 0
                ? $utilizadorId
                : null,
            ':atualizado_por' =>
            $utilizadorId > 0
                ? $utilizadorId
                : null,
            ':id' => $id,
            ':empresa_id' => $empresaId
        ]);

        return [
            'success' => true,
            'message' =>
            'Processamento salarial aprovado com sucesso.',
            'id' => $id
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Cancelar
    |--------------------------------------------------------------------------
    */

    public function cancelar(
        int $id,
        int $empresaId,
        int $utilizadorId,
        string $motivo
    ): array {
        $motivo = trim($motivo);

        if ($motivo === '') {
            throw new Exception(
                'Informe o motivo do cancelamento.'
            );
        }

        $processamento =
            $this->buscar($id, $empresaId);

        if (!$processamento) {
            throw new Exception(
                'Processamento salarial não encontrado.'
            );
        }

        if (
            in_array(
                $processamento['estado'],
                [
                    self::STATUS_PROCESSAMENTO_PAGO,
                    self::STATUS_PROCESSAMENTO_CANCELADO
                ],
                true
            )
        ) {
            throw new Exception(
                'Este processamento não pode ser cancelado.'
            );
        }

        $statusId =
            $this->obterStatusId(
                self::TIPO_STATUS_PROCESSAMENTO,
                self::STATUS_PROCESSAMENTO_CANCELADO
            );

        $stmt = $this->db->prepare("
            UPDATE processamentos_salariais

            SET
                status_id = :status_id,
                cancelado_por = :cancelado_por,
                cancelado_em = NOW(),
                motivo_cancelamento = :motivo,
                atualizado_por = :atualizado_por

            WHERE id = :id
              AND empresa_id = :empresa_id

            LIMIT 1
        ");

        $stmt->execute([
            ':status_id' => $statusId,
            ':cancelado_por' =>
            $utilizadorId > 0
                ? $utilizadorId
                : null,
            ':motivo' => $motivo,
            ':atualizado_por' =>
            $utilizadorId > 0
                ? $utilizadorId
                : null,
            ':id' => $id,
            ':empresa_id' => $empresaId
        ]);

        return [
            'success' => true,
            'message' =>
            'Processamento salarial cancelado com sucesso.',
            'id' => $id
        ];
    }

    /*
|--------------------------------------------------------------------------
| CALCULAR BASE CONTRIBUTIVA DO INSS
|--------------------------------------------------------------------------
*/

    private function calcularBaseInss(
        int $processamentoId
    ): float {

        if ($processamentoId <= 0) {
            return 0.00;
        }


        /*
    |--------------------------------------------------------------------------
    | Apenas PROVENTOS tributáveis
    |--------------------------------------------------------------------------
    |
    | Não podemos somar descontos nem o próprio INSS.
    |--------------------------------------------------------------------------
    */

        $stmt =
            $this->db->prepare("
            SELECT
                COALESCE(
                    SUM(valor_total),
                    0
                )

            FROM processamento_salario_itens

            WHERE processamento_id =
                :processamento_id

              AND tipo = 'PROVENTO'

              AND tributavel_inss = 1
        ");


        $stmt->execute([
            ':processamento_id' =>
            $processamentoId
        ]);


        return round(
            (float)$stmt->fetchColumn(),
            2
        );
    }

    /*
|--------------------------------------------------------------------------
| CALCULAR BASE TRIBUTÁVEL DO IRPS
|--------------------------------------------------------------------------
*/

    private function calcularBaseIrps(
        int $processamentoId
    ): float {

        if ($processamentoId <= 0) {
            return 0.00;
        }


        $stmt =
            $this->db->prepare("
            SELECT
                COALESCE(
                    SUM(valor_total),
                    0
                )

            FROM processamento_salario_itens

            WHERE processamento_id =
                :processamento_id

              AND tipo = 'PROVENTO'

              AND tributavel_irps = 1
        ");


        $stmt->execute([
            ':processamento_id' =>
            $processamentoId
        ]);


        return round(
            (float)$stmt->fetchColumn(),
            2
        );
    }

    /*
|--------------------------------------------------------------------------
| DADOS FISCAIS DO FUNCIONÁRIO
|--------------------------------------------------------------------------
*/

    private function buscarDadosFiscaisFuncionario(
        int $funcionarioId
    ): array {

        $stmt =
            $this->db->prepare("
            SELECT
                numero_dependentes,
                residente_fiscal,
                nuit

            FROM funcionario_dados_fiscais

            WHERE funcionario_id =
                :funcionario_id

            LIMIT 1
        ");


        $stmt->execute([
            ':funcionario_id' =>
            $funcionarioId
        ]);


        $dados =
            $stmt->fetch(
                PDO::FETCH_ASSOC
            );


        /*
    |--------------------------------------------------------------------------
    | Se ainda não houver configuração fiscal
    |--------------------------------------------------------------------------
    |
    | Para não assumir silenciosamente dependentes,
    | prefiro bloquear o processamento.
    |--------------------------------------------------------------------------
    */

        if (!$dados) {
            throw new RuntimeException(
                'Os dados fiscais do funcionário não estão configurados.'
            );
        }


        return $dados;
    }

    /*
|--------------------------------------------------------------------------
| TABELA IRPS VIGENTE
|--------------------------------------------------------------------------
*/

    private function buscarTabelaIrpsVigente(
        int $ano
    ): array {

        $stmt =
            $this->db->prepare("
            SELECT
                id,
                nome,
                ano_inicio,
                ano_fim,
                moeda

            FROM irps_tabelas

            WHERE ativo = 1

              AND ano_inicio <= :ano_inicio

              AND (
                    ano_fim IS NULL
                    OR ano_fim >= :ano_fim
              )

            ORDER BY
                ano_inicio DESC,
                id DESC

            LIMIT 1
        ");


        $stmt->execute([
            ':ano_inicio' =>
            $ano,

            ':ano_fim' =>
            $ano
        ]);


        $tabela =
            $stmt->fetch(
                PDO::FETCH_ASSOC
            );


        if (!$tabela) {

            throw new RuntimeException(
                'Não existe uma tabela nacional de IRPS activa '
                    . 'para o ano '
                    . $ano
                    . '.'
            );
        }


        return $tabela;
    }

    /*
|--------------------------------------------------------------------------
| ESCALÃO IRPS
|--------------------------------------------------------------------------
*/

    private function buscarEscalaoIrps(
        int $tabelaId,
        float $baseIrps
    ): ?array {

        $stmt =
            $this->db->prepare("
            SELECT
                *

            FROM irps_escaloes

            WHERE tabela_id =
                :tabela_id

              AND limite_inferior <=
                :base_inferior

              AND (
                    limite_superior IS NULL
                    OR limite_superior >=
                        :base_superior
              )

            ORDER BY ordem ASC

            LIMIT 1
        ");


        $stmt->execute([
            ':tabela_id' =>
            $tabelaId,

            ':base_inferior' =>
            $baseIrps,

            ':base_superior' =>
            $baseIrps
        ]);


        $escalao =
            $stmt->fetch(
                PDO::FETCH_ASSOC
            );


        return $escalao ?: null;
    }

    /*
|--------------------------------------------------------------------------
| CALCULAR IRPS
|--------------------------------------------------------------------------
*/

    private function calcularIrps(
        int $funcionarioId,
        int $processamentoId,
        array $periodo
    ): array {

        /*
    |--------------------------------------------------------------------------
    | Base tributável
    |--------------------------------------------------------------------------
    */

        $baseIrps =
            $this->calcularBaseIrps(
                $processamentoId
            );


        if ($baseIrps <= 0) {

            return [
                'base' => 0.00,
                'valor' => 0.00,
                'percentagem' => 0.00,
                'dependentes' => 0,
                'tabela_id' => null,
                'escalao_id' => null
            ];
        }


        /*
    |--------------------------------------------------------------------------
    | Dados fiscais
    |--------------------------------------------------------------------------
    */

        $dadosFiscais =
            $this->buscarDadosFiscaisFuncionario(
                $funcionarioId
            );


        if (
            (int)$dadosFiscais['residente_fiscal'] !== 1
        ) {
            throw new RuntimeException(
                'O cálculo automático de IRPS está configurado '
                    . 'apenas para trabalhadores residentes fiscais.'
            );
        }


        $dependentes =
            max(
                0,
                (int)(
                    $dadosFiscais['numero_dependentes']
                    ?? 0
                )
            );


        /*
    |--------------------------------------------------------------------------
    | Ano
    |--------------------------------------------------------------------------
    */

        $ano =
            (int)date(
                'Y',
                strtotime(
                    (string)$periodo['data_inicio']
                )
            );


        /*
    |--------------------------------------------------------------------------
    | Tabela vigente
    |--------------------------------------------------------------------------
    */

        $tabela =
            $this->buscarTabelaIrpsVigente(
                $ano
            );


        /*
    |--------------------------------------------------------------------------
    | Escalão
    |--------------------------------------------------------------------------
    */

        $escalao =
            $this->buscarEscalaoIrps(
                (int)$tabela['id'],
                $baseIrps
            );


        if (!$escalao) {
            throw new RuntimeException(
                'Não foi encontrado um escalão de IRPS '
                    . 'para a remuneração tributável de '
                    . number_format(
                        $baseIrps,
                        2,
                        ',',
                        '.'
                    )
                    . ' MTn.'
            );
        }


        /*
    |--------------------------------------------------------------------------
    | Coluna conforme dependentes
    |--------------------------------------------------------------------------
    */

        $campoBase =
            match (true) {

                $dependentes <= 0 =>
                'valor_base_0',

                $dependentes === 1 =>
                'valor_base_1',

                $dependentes === 2 =>
                'valor_base_2',

                $dependentes === 3 =>
                'valor_base_3',

                default =>
                'valor_base_4_mais'
            };


        /*
    |--------------------------------------------------------------------------
    | NULL significa sem retenção neste escalão/situação
    |--------------------------------------------------------------------------
    */

        if (
            $escalao[$campoBase]
            === null
        ) {

            return [
                'base' =>
                $baseIrps,

                'valor' =>
                0.00,

                'percentagem' =>
                0.00,

                'dependentes' =>
                $dependentes,

                'tabela_id' =>
                (int)$tabela['id'],

                'escalao_id' =>
                (int)$escalao['id']
            ];
        }


        $valorBase =
            (float)$escalao[$campoBase];


        $coeficiente =
            (float)(
                $escalao['coeficiente']
                ?? 0
            );


        $limiteInferior =
            (float)$escalao['limite_inferior'];


        /*
    |--------------------------------------------------------------------------
    | Cálculo parametrizado
    |--------------------------------------------------------------------------
    */

        $excesso =
            max(
                0,
                $baseIrps
                    - $limiteInferior
            );


        $valorIrps =
            round(
                $valorBase
                    + (
                        $excesso
                        * $coeficiente
                    ),
                2
            );


        return [
            'base' =>
            $baseIrps,

            'valor' =>
            max(
                $valorIrps,
                0
            ),

            'percentagem' =>
            round(
                $coeficiente * 100,
                2
            ),

            'dependentes' =>
            $dependentes,

            'tabela_id' =>
            (int)$tabela['id'],

            'escalao_id' =>
            (int)$escalao['id']
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Resumo
    |--------------------------------------------------------------------------
    */

    public function resumo(
        int $empresaId,
        ?int $periodoId = null
    ): array {
        $sql = "
            SELECT
                COUNT(*) AS quantidade,

                COALESCE(
                    SUM(salario_bruto),
                    0
                ) AS total_bruto,

                COALESCE(
                    SUM(total_descontos),
                    0
                ) AS total_descontos,

                COALESCE(
                    SUM(salario_liquido),
                    0
                ) AS total_liquido,

                COALESCE(
                    SUM(valor_pago),
                    0
                ) AS total_pago,

                COALESCE(
                    SUM(saldo_pendente),
                    0
                ) AS total_pendente

            FROM processamentos_salariais

            WHERE empresa_id = :empresa_id
        ";

        $params = [
            ':empresa_id' => $empresaId
        ];

        if ($periodoId !== null) {
            $sql .= "
                AND periodo_id = :periodo_id
            ";

            $params[':periodo_id'] =
                $periodoId;
        }

        $stmt = $this->db->prepare($sql);

        $stmt->execute($params);

        $resumo = $stmt->fetch(
            PDO::FETCH_ASSOC
        );

        return [
            'quantidade' =>
            (int)($resumo['quantidade'] ?? 0),

            'total_bruto' =>
            (float)($resumo['total_bruto'] ?? 0),

            'total_descontos' =>
            (float)(
                $resumo['total_descontos']
                ?? 0
            ),

            'total_liquido' =>
            (float)(
                $resumo['total_liquido']
                ?? 0
            ),

            'total_pago' =>
            (float)($resumo['total_pago'] ?? 0),

            'total_pendente' =>
            (float)(
                $resumo['total_pendente']
                ?? 0
            )
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Consultas auxiliares
    |--------------------------------------------------------------------------
    */

    private function buscarPeriodo(
        int $periodoId,
        int $empresaId
    ): ?array {
        $sql = "
        SELECT
            ps.*,
            st.nome AS estado
        FROM periodos_salariais ps

        INNER JOIN status st
            ON st.id = ps.status_id
           AND st.tipo = :tipo_status

        WHERE ps.id = :periodo_id
          AND ps.empresa_id = :empresa_id

        LIMIT 1
    ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            'tipo_status' =>
            'periodo_salarial',

            'periodo_id' =>
            $periodoId,

            'empresa_id' =>
            $empresaId
        ]);

        $periodo = $stmt->fetch(PDO::FETCH_ASSOC);

        return $periodo ?: null;
    }

    private function contarProcessamentosPeriodo(
        int $periodoId,
        int $empresaId
    ): int {
        $stmt = $this->db->prepare("
            SELECT COUNT(*)

            FROM processamentos_salariais

            WHERE periodo_id = :periodo_id
              AND empresa_id = :empresa_id
        ");

        $stmt->execute([
            ':periodo_id' => $periodoId,
            ':empresa_id' => $empresaId
        ]);

        return (int)$stmt->fetchColumn();
    }

    private function listarFuncionariosProcessaveis(
        int $empresaId,
        string $dataInicioPeriodo,
        string $dataFimPeriodo
    ): array {
        $statusFuncionarioActivo =
            $this->obterStatusId(
                'funcionario',
                'Activo'
            );

        $statusContratoActivo =
            $this->obterStatusId(
                'contrato_trabalho',
                'Activo'
            );

        $sql = "
        SELECT
            f.id AS funcionario_id,
            f.uuid,
            f.numero_funcionario,
            f.nome_completo AS funcionario_nome,
            f.filial_id,
            f.departamento_id,
            f.cargo_id,

            COALESCE(c.nome, '') AS cargo_nome,
            COALESCE(d.nome, '') AS departamento_nome,
            COALESCE(fl.nome, '') AS filial_nome,

            ct.id AS contrato_id,
            ct.salario_base AS contrato_salario_base,
            ct.moeda AS contrato_moeda,
            ct.data_inicio AS contrato_data_inicio,
            ct.data_fim AS contrato_data_fim

        FROM funcionarios f

        INNER JOIN contratos_trabalho ct
            ON ct.funcionario_id = f.id
           AND ct.empresa_id = f.empresa_id
           AND ct.status_id = :status_contrato_activo

        LEFT JOIN cargos c
            ON c.id = f.cargo_id

        LEFT JOIN departamentos d
            ON d.id = f.departamento_id

        LEFT JOIN filiais fl
            ON fl.id = f.filial_id

        WHERE f.empresa_id = :empresa_id
          AND f.status_id = :status_funcionario_activo
          AND ct.data_inicio <= :data_fim_periodo
          AND (
                ct.data_fim IS NULL
                OR ct.data_fim >= :data_inicio_periodo
          )

        ORDER BY f.nome_completo ASC
    ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            'status_contrato_activo' =>
            $statusContratoActivo,

            'empresa_id' =>
            $empresaId,

            'status_funcionario_activo' =>
            $statusFuncionarioActivo,

            'data_fim_periodo' =>
            $dataFimPeriodo,

            'data_inicio_periodo' =>
            $dataInicioPeriodo
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function buscarSalarioVigente(
        int $empresaId,
        int $funcionarioId,
        string $dataInicioPeriodo,
        string $dataFimPeriodo
    ): ?array {
        $statusSalarioActivo =
            $this->obterStatusId(
                'salario_funcionario',
                'Activo'
            );

        $sql = "
        SELECT
            sf.*
        FROM salarios_funcionarios sf
        WHERE sf.empresa_id = :empresa_id
          AND sf.funcionario_id = :funcionario_id
          AND sf.status_id = :status_salario_activo
          AND sf.data_inicio <= :data_fim_periodo
          AND (
                sf.data_fim IS NULL
                OR sf.data_fim >= :data_inicio_periodo
          )
        ORDER BY
            sf.data_inicio DESC,
            sf.id DESC
        LIMIT 1
    ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            'empresa_id' =>
            $empresaId,

            'funcionario_id' =>
            $funcionarioId,

            'status_salario_activo' =>
            $statusSalarioActivo,

            'data_fim_periodo' =>
            $dataFimPeriodo,

            'data_inicio_periodo' =>
            $dataInicioPeriodo
        ]);

        $salario = $stmt->fetch(PDO::FETCH_ASSOC);

        return $salario ?: null;
    }

    private function listarRubricasSalario(
        int $salarioFuncionarioId
    ): array {
        $statusRubricaActiva =
            $this->obterStatusId(
                'rubrica_salarial',
                'Activo'
            );

        $sql = "
        SELECT
            sfr.id AS salario_rubrica_id,
            sfr.salario_funcionario_id,
            sfr.rubrica_id,

            COALESCE(sfr.valor, 0) AS valor,
            COALESCE(sfr.percentagem, 0) AS percentagem,

            1 AS quantidade_padrao,

            sfr.observacoes,

            rs.codigo,
            rs.nome,
            rs.descricao,
            rs.tipo,
            rs.categoria,
            rs.modo_calculo,
            rs.base_calculo,
            rs.formula_calculo,

            COALESCE(rs.valor_padrao, 0)
                AS valor_padrao,

            COALESCE(rs.percentagem_padrao, 0)
                AS percentagem_padrao,

            COALESCE(rs.tributavel_inss, 0)
                AS tributavel_inss,

            COALESCE(rs.tributavel_irps, 0)
                AS tributavel_irps,

            COALESCE(rs.ordem, 0)
                AS ordem

        FROM salario_funcionario_rubricas sfr

        INNER JOIN rubricas_salariais rs
            ON rs.id = sfr.rubrica_id

        WHERE sfr.salario_funcionario_id =
            :salario_funcionario_id

          AND rs.status_id =
            :status_rubrica_activa

        ORDER BY
            rs.ordem ASC,
            rs.nome ASC
    ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            'salario_funcionario_id' =>
            $salarioFuncionarioId,

            'status_rubrica_activa' =>
            $statusRubricaActiva
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /*
    |--------------------------------------------------------------------------
    | Inserções e atualizações
    |--------------------------------------------------------------------------
    */

    private function inserirProcessamento(
        array $dados
    ): int {
        $stmt = $this->db->prepare("
            INSERT INTO processamentos_salariais
            (
                uuid,
                empresa_id,
                periodo_id,
                funcionario_id,
                contrato_id,
                salario_funcionario_id,
                moeda,
                numero_funcionario,
                funcionario_nome,
                cargo_nome,
                departamento_nome,
                filial_nome,
                dias_periodo,
                dias_uteis,
                dias_trabalhados,
                salario_base,
                valor_dia,
                valor_hora,
                total_salario_base,
                status_id,
                processado_por,
                processado_em,
                criado_por
            )
            VALUES
            (
                :uuid,
                :empresa_id,
                :periodo_id,
                :funcionario_id,
                :contrato_id,
                :salario_funcionario_id,
                :moeda,
                :numero_funcionario,
                :funcionario_nome,
                :cargo_nome,
                :departamento_nome,
                :filial_nome,
                :dias_periodo,
                :dias_uteis,
                :dias_trabalhados,
                :salario_base,
                :valor_dia,
                :valor_hora,
                :total_salario_base,
                :status_id,
                :processado_por,
                NOW(),
                :criado_por
            )
        ");

        $utilizadorId =
            (int)$dados['utilizador_id'];

        $stmt->execute([
            ':uuid' =>
            $this->gerarUUID(),

            ':empresa_id' =>
            $dados['empresa_id'],

            ':periodo_id' =>
            $dados['periodo_id'],

            ':funcionario_id' =>
            $dados['funcionario_id'],

            ':contrato_id' =>
            $dados['contrato_id'],

            ':salario_funcionario_id' =>
            $dados['salario_funcionario_id'],

            ':moeda' =>
            strtoupper(
                (string)$dados['moeda']
            ),

            ':numero_funcionario' =>
            $dados['numero_funcionario'],

            ':funcionario_nome' =>
            $dados['funcionario_nome'],

            ':cargo_nome' =>
            $dados['cargo_nome'],

            ':departamento_nome' =>
            $dados['departamento_nome'],

            ':filial_nome' =>
            $dados['filial_nome'],

            ':dias_periodo' =>
            $dados['dias_periodo'],

            ':dias_uteis' =>
            $dados['dias_uteis'],

            ':dias_trabalhados' =>
            $dados['dias_trabalhados'],

            ':salario_base' =>
            $dados['salario_base'],

            ':valor_dia' =>
            $dados['valor_dia'],

            ':valor_hora' =>
            $dados['valor_hora'],

            ':total_salario_base' =>
            $dados['total_salario_base'],

            ':status_id' =>
            $dados['status_id'],

            ':processado_por' =>
            $utilizadorId > 0
                ? $utilizadorId
                : null,

            ':criado_por' =>
            $utilizadorId > 0
                ? $utilizadorId
                : null
        ]);

        return (int)$this->db
            ->lastInsertId();
    }

    private function inserirItem(
        array $dados
    ): void {
        $stmt = $this->db->prepare("
            INSERT INTO processamento_salario_itens
            (
                processamento_id,
                rubrica_id,
                codigo_rubrica,
                nome_rubrica,
                descricao,
                tipo,
                categoria,
                origem,
                origem_registo_id,
                quantidade,
                valor_unitario,
                percentagem,
                base_calculo,
                valor_total,
                tributavel_inss,
                tributavel_irps,
                automatico,
                ordem,
                observacoes,
                criado_por
            )
            VALUES
            (
                :processamento_id,
                :rubrica_id,
                :codigo_rubrica,
                :nome_rubrica,
                :descricao,
                :tipo,
                :categoria,
                :origem,
                :origem_registo_id,
                :quantidade,
                :valor_unitario,
                :percentagem,
                :base_calculo,
                :valor_total,
                :tributavel_inss,
                :tributavel_irps,
                :automatico,
                :ordem,
                :observacoes,
                :criado_por
            )
        ");

        $stmt->execute([
            ':processamento_id' =>
            $dados['processamento_id'],

            ':rubrica_id' =>
            $dados['rubrica_id'],

            ':codigo_rubrica' =>
            $dados['codigo_rubrica'],

            ':nome_rubrica' =>
            $dados['nome_rubrica'],

            ':descricao' =>
            $dados['descricao'],

            ':tipo' =>
            $dados['tipo'],

            ':categoria' =>
            $dados['categoria'],

            ':origem' =>
            $dados['origem'],

            ':origem_registo_id' =>
            $dados['origem_registo_id'],

            ':quantidade' =>
            $dados['quantidade'],

            ':valor_unitario' =>
            $dados['valor_unitario'],

            ':percentagem' =>
            $dados['percentagem'],

            ':base_calculo' =>
            $dados['base_calculo'],

            ':valor_total' =>
            $dados['valor_total'],

            ':tributavel_inss' =>
            $dados['tributavel_inss'],

            ':tributavel_irps' =>
            $dados['tributavel_irps'],

            ':automatico' =>
            $dados['automatico'],

            ':ordem' =>
            $dados['ordem'],

            ':observacoes' =>
            $dados['observacoes'],

            ':criado_por' =>
            $dados['criado_por'] > 0
                ? $dados['criado_por']
                : null
        ]);
    }

    private function atualizarTotaisProcessamento(
        int $id,
        int $empresaId,
        array $totais
    ): void {
        $stmt = $this->db->prepare("
            UPDATE processamentos_salariais

            SET
                total_subsidios =
                    :total_subsidios,

                total_horas_extras =
                    :total_horas_extras,

                total_outros_proventos =
                    :total_outros_proventos,

                total_proventos =
                    :total_proventos,

                total_faltas =
                    :total_faltas,

                total_atrasos =
                    :total_atrasos,

                total_inss =
                    :total_inss,

                total_inss_patronal =
                    :total_inss_patronal,

                total_irps =
                    :total_irps,

                total_outros_descontos =
                    :total_outros_descontos,

                total_descontos =
                    :total_descontos,

                salario_bruto =
                    :salario_bruto,

                salario_liquido =
                    :salario_liquido,

                saldo_pendente =
                    :saldo_pendente,

                atualizado_por =
                    :atualizado_por

            WHERE id = :id
              AND empresa_id = :empresa_id

            LIMIT 1
        ");

        $stmt->execute([
            ':total_subsidios' =>
            $totais['total_subsidios'],

            ':total_horas_extras' =>
            $totais['total_horas_extras'],

            ':total_outros_proventos' =>
            $totais['total_outros_proventos'],

            ':total_proventos' =>
            $totais['total_proventos'],

            ':total_faltas' =>
            $totais['total_faltas'],

            ':total_atrasos' =>
            $totais['total_atrasos'],

            ':total_inss' =>
            $totais['total_inss'],

            ':total_inss_patronal' =>
            $totais['total_inss_patronal'],

            ':total_irps' =>
            $totais['total_irps'],

            ':total_outros_descontos' =>
            $totais['total_outros_descontos'],

            ':total_descontos' =>
            $totais['total_descontos'],

            ':salario_bruto' =>
            $totais['salario_bruto'],

            ':salario_liquido' =>
            $totais['salario_liquido'],

            ':saldo_pendente' =>
            $totais['saldo_pendente'],

            ':atualizado_por' =>
            $totais['utilizador_id'] > 0
                ? $totais['utilizador_id']
                : null,

            ':id' => $id,
            ':empresa_id' => $empresaId
        ]);
    }

    private function alterarStatusPeriodo(
        int $periodoId,
        int $empresaId,
        string $nomeStatus
    ): void {
        $statusId = $this->obterStatusId(
            'periodo_salarial',
            $nomeStatus
        );

        $sql = "
        UPDATE periodos_salariais
        SET
            status_id = :status_id,
            atualizado_em = NOW()
        WHERE id = :periodo_id
          AND empresa_id = :empresa_id
    ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            'status_id' =>
            $statusId,

            'periodo_id' =>
            $periodoId,

            'empresa_id' =>
            $empresaId
        ]);
    }


    /*
|--------------------------------------------------------------------------
| Resumo da assiduidade para processamento
|--------------------------------------------------------------------------
*/

    private function buscarResumoAssiduidadeProcessamento(
        int $empresaId,
        int $funcionarioId,
        string $dataInicio,
        string $dataFim
    ): array {

        $stmt =
            $this->db->prepare("
            SELECT
                COUNT(
                    CASE
                        WHEN ad.presente = 1
                        THEN 1
                    END
                ) AS dias_trabalhados,

                COALESCE(
                    SUM(ad.minutos_atraso),
                    0
                ) AS minutos_atraso,

                COALESCE(
                    SUM(ad.minutos_trabalhados),
                    0
                ) AS minutos_trabalhados

            FROM assiduidade_diaria ad

            INNER JOIN status st
                ON st.id = ad.status_id
               AND st.tipo = 'assiduidade_diaria'

            WHERE ad.empresa_id =
                :empresa_id

              AND ad.funcionario_id =
                :funcionario_id

              AND ad.data_assiduidade
                  BETWEEN :data_inicio
                  AND :data_fim

              AND LOWER(st.nome) IN (
                  'apurada',
                  'aprovada',
                  'fechada'
              )
        ");


        $stmt->execute([
            ':empresa_id' =>
            $empresaId,

            ':funcionario_id' =>
            $funcionarioId,

            ':data_inicio' =>
            $dataInicio,

            ':data_fim' =>
            $dataFim
        ]);


        $dados =
            $stmt->fetch(
                PDO::FETCH_ASSOC
            ) ?: [];


        return [
            'dias_trabalhados' =>
            (float)(
                $dados['dias_trabalhados']
                ?? 0
            ),

            'minutos_atraso' =>
            (int)(
                $dados['minutos_atraso']
                ?? 0
            ),

            'minutos_trabalhados' =>
            (int)(
                $dados['minutos_trabalhados']
                ?? 0
            )
        ];
    }

    private function listarFaltasProcessamento(
        int $empresaId,
        int $funcionarioId,
        string $dataInicio,
        string $dataFim
    ): array {

        $stmt =
            $this->db->prepare("
            SELECT
                f.id,
                f.assiduidade_diaria_id,
                f.data_inicio,
                f.data_fim,
                f.tipo_falta,
                f.origem,
                f.quantidade_dias,
                f.minutos_falta,
                f.remunerada,
                f.desconta_salario,

                COALESCE(
                    ad.minutos_atraso,
                    0
                ) AS minutos_atraso,

                st.nome AS estado

            FROM faltas f

            INNER JOIN status st
                ON st.id = f.status_id
               AND st.tipo = 'falta'

            LEFT JOIN assiduidade_diaria ad
                ON ad.id =
                    f.assiduidade_diaria_id

               AND ad.empresa_id =
                    f.empresa_id

            WHERE f.empresa_id =
                :empresa_id

              AND f.funcionario_id =
                :funcionario_id

              AND f.desconta_salario = 1

              AND f.data_inicio <=
                    :data_fim

              AND f.data_fim >=
                    :data_inicio

            ORDER BY
                f.data_inicio ASC,
                f.id ASC
        ");


        $stmt->execute([
            ':empresa_id' =>
            $empresaId,

            ':funcionario_id' =>
            $funcionarioId,

            ':data_inicio' =>
            $dataInicio,

            ':data_fim' =>
            $dataFim
        ]);


        return $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );
    }

    private function listarHorasExtrasProcessamento(
        int $empresaId,
        int $funcionarioId,
        string $dataInicio,
        string $dataFim
    ): array {

        $stmt =
            $this->db->prepare("
            SELECT
                he.id,
                he.assiduidade_diaria_id,
                he.data_hora_extra,
                he.tipo_hora_extra,
                he.origem,
                he.minutos_apurados,
                he.minutos_aprovados,
                he.percentual_acrescimo,
                he.valor_hora,
                he.valor_total,
                st.nome AS estado

            FROM horas_extras he

            INNER JOIN status st
                ON st.id = he.status_id
               AND st.tipo = 'hora_extra'

            WHERE he.empresa_id =
                :empresa_id

              AND he.funcionario_id =
                :funcionario_id

              AND he.data_hora_extra
                  BETWEEN :data_inicio
                  AND :data_fim

              AND LOWER(st.nome) =
                    'aprovada'

              AND he.minutos_aprovados > 0

              AND he.valor_total > 0

            ORDER BY
                he.data_hora_extra ASC,
                he.id ASC
        ");


        $stmt->execute([
            ':empresa_id' =>
            $empresaId,

            ':funcionario_id' =>
            $funcionarioId,

            ':data_inicio' =>
            $dataInicio,

            ':data_fim' =>
            $dataFim
        ]);


        return $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );
    }

    private function buscarRubricaAutomatica(
        int $empresaId,
        string $categoria
    ): ?array {

        $statusId =
            $this->obterStatusId(
                'rubrica_salarial',
                'Activo'
            );

        $categoria =
            strtoupper(
                trim(
                    $categoria
                )
            );

        $stmt =
            $this->db->prepare("
            SELECT
                rs.id,
                rs.codigo,
                rs.nome,
                rs.descricao,
                rs.tipo,
                rs.categoria,
                rs.tributavel_inss,
                rs.tributavel_irps,
                rs.ordem

            FROM rubricas_salariais rs

            WHERE rs.empresa_id =
                :empresa_id

              AND rs.categoria =
                :categoria

              AND rs.status_id =
                :status_id

            ORDER BY
                rs.id ASC

            LIMIT 1
        ");

        $stmt->execute([
            ':empresa_id' =>
            $empresaId,

            ':categoria' =>
            $categoria,

            ':status_id' =>
            $statusId
        ]);

        $rubrica =
            $stmt->fetch(
                PDO::FETCH_ASSOC
            );

        return $rubrica
            ?: null;
    }

    /*
    |--------------------------------------------------------------------------
    | Status e UUID
    |--------------------------------------------------------------------------
    */

    private function obterStatusId(
        string $tipo,
        string $nome
    ): int {
        $sql = "
        SELECT id
        FROM status
        WHERE tipo = :tipo
          AND nome = :nome
        LIMIT 1
    ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            'tipo' => $tipo,
            'nome' => $nome
        ]);

        $statusId = $stmt->fetchColumn();

        if (!$statusId) {
            throw new RuntimeException(
                sprintf(
                    'Estado não encontrado: tipo=%s, nome=%s.',
                    $tipo,
                    $nome
                )
            );
        }

        return (int) $statusId;
    }

    private function gerarUUID(): string
    {
        $dados =
            random_bytes(16);

        $dados[6] =
            chr(
                (
                    ord($dados[6])
                    & 0x0f
                )
                    | 0x40
            );

        $dados[8] =
            chr(
                (
                    ord($dados[8])
                    & 0x3f
                )
                    | 0x80
            );

        return vsprintf(
            '%s%s-%s-%s-%s-%s%s%s',
            str_split(
                bin2hex($dados),
                4
            )
        );
    }
}
