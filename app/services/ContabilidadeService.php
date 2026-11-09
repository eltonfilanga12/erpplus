<?php

declare(strict_types=1);

final class ContabilidadeService
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    // ==================================================
    // VERIFICAR LANÇAMENTO EXISTENTE
    // ==================================================

    public function existeLancamentoOrigem(
        int $empresaId,
        ?int $filialId,
        string $origem,
        int $origemId
    ): bool {

        $sql = "
            SELECT COUNT(*)

            FROM lancamentos_contabilisticos

            WHERE empresa_id =
                :empresa_id

              AND origem =
                :origem

              AND origem_id =
                :origem_id
        ";

        $params = [
            ':empresa_id' =>
            $empresaId,

            ':origem' =>
            strtoupper(
                trim($origem)
            ),

            ':origem_id' =>
            $origemId
        ];

        if ($filialId === null) {
            $sql .= "
                AND filial_id IS NULL
            ";
        } else {
            $sql .= "
                AND filial_id =
                    :filial_id
            ";

            $params[':filial_id'] =
                $filialId;
        }

        $stmt =
            $this->pdo->prepare($sql);

        $stmt->execute($params);

        return (
            (int)$stmt->fetchColumn()
            > 0
        );
    }

    // ==================================================
    // LOCALIZAR CONTA DE INTEGRAÇÃO
    // ==================================================

    public function contaIntegracao(
        int $empresaId,
        string $codigo
    ): array {

        $codigo =
            strtoupper(
                trim($codigo)
            );

        $stmt = $this->pdo->prepare("
            SELECT
                conta.id,
                conta.codigo,
                conta.nome,
                conta.tipo,
                conta.aceita_lancamento,
                conta.status_id,

                status.nome AS status_nome

            FROM contas_contabilisticas conta

            INNER JOIN status
                ON status.id =
                    conta.status_id

               AND status.tipo =
                    'conta_contabilistica'

            WHERE conta.empresa_id =
                :empresa_id

              AND conta.integracao_codigo =
                :integracao_codigo

            LIMIT 1
        ");

        $stmt->execute([
            ':empresa_id' =>
            $empresaId,

            ':integracao_codigo' =>
            $codigo
        ]);

        $conta =
            $stmt->fetch(
                PDO::FETCH_ASSOC
            );

        if (!$conta) {
            throw new RuntimeException(
                "Conta contabilística de integração '{$codigo}' não configurada."
            );
        }

        if (
            strtoupper(
                (string)$conta['tipo']
            ) !== 'ANALITICA'

            || (int)$conta['aceita_lancamento'] !== 1
        ) {
            throw new RuntimeException(
                "A conta de integração '{$codigo}' não aceita lançamentos."
            );
        }

        if (
            mb_strtolower(
                trim(
                    (string)$conta['status_nome']
                )
            ) !== 'activa'
        ) {
            throw new RuntimeException(
                "A conta de integração '{$codigo}' não está ativa."
            );
        }

        return $conta;
    }

    // ==================================================
    // LOCALIZAR DIÁRIO
    // ==================================================

    public function diarioPorTipo(
        int $empresaId,
        string $tipo
    ): array {

        $tipo =
            strtoupper(
                trim($tipo)
            );

        $stmt = $this->pdo->prepare("
            SELECT
                diario.id,
                diario.codigo,
                diario.nome,
                diario.tipo,
                diario.numeracao_automatica,
                diario.ultimo_numero,
                diario.status_id,

                status.nome AS status_nome

            FROM diarios_contabilisticos diario

            INNER JOIN status
                ON status.id =
                    diario.status_id

               AND status.tipo =
                    'diario_contabilistico'

            WHERE diario.empresa_id =
                :empresa_id

              AND diario.tipo =
                :tipo

              AND LOWER(status.nome) =
                'activo'

            ORDER BY diario.id ASC

            LIMIT 1
        ");

        $stmt->execute([
            ':empresa_id' =>
            $empresaId,

            ':tipo' =>
            $tipo
        ]);

        $diario =
            $stmt->fetch(
                PDO::FETCH_ASSOC
            );

        if (!$diario) {
            throw new RuntimeException(
                "Diário contabilístico ativo do tipo '{$tipo}' não encontrado."
            );
        }

        if (
            (int)$diario['numeracao_automatica'] !== 1
        ) {
            throw new RuntimeException(
                "O diário '{$tipo}' deve utilizar numeração automática."
            );
        }

        return $diario;
    }

    // ==================================================
    // LOCALIZAR EXERCÍCIO E PERÍODO
    // ==================================================

    public function contextoData(
        int $empresaId,
        string $data
    ): array {

        $this->validarData(
            $data
        );

        $stmt = $this->pdo->prepare("
            SELECT
                exercicio.id AS exercicio_id,
                exercicio.codigo AS exercicio_codigo,

                periodo.id AS periodo_id,
                periodo.numero AS periodo_numero,
                periodo.nome AS periodo_nome,

                status_exercicio.nome
                    AS exercicio_status,

                status_periodo.nome
                    AS periodo_status

            FROM exercicios_contabilisticos exercicio

            INNER JOIN periodos_contabilisticos periodo
                ON periodo.exercicio_id =
                    exercicio.id

               AND periodo.empresa_id =
                    exercicio.empresa_id

            INNER JOIN status status_exercicio
                ON status_exercicio.id =
                    exercicio.status_id

               AND status_exercicio.tipo =
                    'exercicio_contabilistico'

            INNER JOIN status status_periodo
                ON status_periodo.id =
                    periodo.status_id

               AND status_periodo.tipo =
                    'periodo_contabilistico'

            WHERE exercicio.empresa_id =
                :empresa_id

              AND :data_exercicio BETWEEN
                  exercicio.data_inicio
                  AND exercicio.data_fim

              AND :data_periodo BETWEEN
                  periodo.data_inicio
                  AND periodo.data_fim

            LIMIT 1
        ");

        $stmt->execute([
            ':empresa_id' =>
            $empresaId,

            ':data_exercicio' =>
            $data,

            ':data_periodo' =>
            $data
        ]);

        $contexto =
            $stmt->fetch(
                PDO::FETCH_ASSOC
            );

        if (!$contexto) {
            throw new RuntimeException(
                'Não existe exercício e período contabilístico para a data informada.'
            );
        }

        if (
            mb_strtolower(
                trim(
                    (string)$contexto['exercicio_status']
                )
            ) !== 'aberto'
        ) {
            throw new RuntimeException(
                'O exercício contabilístico não está aberto.'
            );
        }

        if (
            mb_strtolower(
                trim(
                    (string)$contexto['periodo_status']
                )
            ) !== 'aberto'
        ) {
            throw new RuntimeException(
                'O período contabilístico não está aberto.'
            );
        }

        return $contexto;
    }

    // ==================================================
    // STATUS DO LANÇAMENTO
    // ==================================================

    public function statusLancamentoId(
        string $nome
    ): int {

        $stmt = $this->pdo->prepare("
            SELECT id

            FROM status

            WHERE tipo =
                'lancamento_contabilistico'

              AND LOWER(nome) =
                LOWER(:nome)

            LIMIT 1
        ");

        $stmt->execute([
            ':nome' =>
            trim($nome)
        ]);

        $id =
            (int)$stmt->fetchColumn();

        if ($id <= 0) {
            throw new RuntimeException(
                "Status '{$nome}' para lançamento contabilístico não encontrado."
            );
        }

        return $id;
    }

    // ==================================================
    // RESERVAR NÚMERO DO DIÁRIO
    // ==================================================

    public function reservarNumeroDiario(
        int $diarioId,
        int $empresaId
    ): int {

        if ($diarioId <= 0) {
            throw new RuntimeException(
                'Diário contabilístico inválido.'
            );
        }

        if ($empresaId <= 0) {
            throw new RuntimeException(
                'Empresa inválida.'
            );
        }

        /*
     * A atualização é atómica.
     *
     * GREATEST evita reutilizar um número caso
     * ultimo_numero esteja atrasado em relação
     * aos lançamentos existentes.
     *
     * LAST_INSERT_ID permite recuperar exatamente
     * o número reservado por esta ligação.
     */
        $stmt = $this->pdo->prepare("
        UPDATE diarios_contabilisticos diario

        SET diario.ultimo_numero =
            LAST_INSERT_ID(
                GREATEST(
                    COALESCE(
                        diario.ultimo_numero,
                        0
                    ),

                    COALESCE(
                        (
                            SELECT MAX(
                                lancamento.numero
                            )

                            FROM lancamentos_contabilisticos lancamento

                            WHERE lancamento.empresa_id =
                                :empresa_lancamento_id

                              AND lancamento.diario_id =
                                :lancamento_diario_id
                        ),
                        0
                    )
                ) + 1
            )

        WHERE diario.id =
            :diario_id

          AND diario.empresa_id =
            :empresa_id

          AND diario.numeracao_automatica = 1
    ");

        $stmt->execute([
            ':empresa_lancamento_id' =>
            $empresaId,

            ':lancamento_diario_id' =>
            $diarioId,

            ':diario_id' =>
            $diarioId,

            ':empresa_id' =>
            $empresaId
        ]);

        if ($stmt->rowCount() !== 1) {

            $stmtDiario = $this->pdo->prepare("
            SELECT
                id,
                numeracao_automatica

            FROM diarios_contabilisticos

            WHERE id =
                :diario_id

              AND empresa_id =
                :empresa_id

            LIMIT 1
        ");

            $stmtDiario->execute([
                ':diario_id' =>
                $diarioId,

                ':empresa_id' =>
                $empresaId
            ]);

            $diario =
                $stmtDiario->fetch(
                    PDO::FETCH_ASSOC
                );

            if (!$diario) {
                throw new RuntimeException(
                    'Diário contabilístico não encontrado.'
                );
            }

            throw new RuntimeException(
                'O diário contabilístico não utiliza numeração automática.'
            );
        }

        $numero = (int)$this->pdo
            ->query(
                'SELECT LAST_INSERT_ID()'
            )
            ->fetchColumn();

        if ($numero <= 0) {
            throw new RuntimeException(
                'Não foi possível reservar o número do lançamento contabilístico.'
            );
        }

        return $numero;
    }

    // ==================================================
    // CRIAR LANÇAMENTO CONTABILIZADO
    // ==================================================

    public function criarLancamento(
        array $cabecalho,
        array $itens
    ): int {

        if (count($itens) < 2) {
            throw new RuntimeException(
                'O lançamento contabilístico deve possuir pelo menos dois itens.'
            );
        }

        $totalDebito = 0.0;
        $totalCredito = 0.0;

        foreach ($itens as $indice => $item) {
            $debito =
                round(
                    (float)(
                        $item['debito']
                        ?? 0
                    ),
                    2
                );

            $credito =
                round(
                    (float)(
                        $item['credito']
                        ?? 0
                    ),
                    2
                );

            if (
                $debito <= 0
                && $credito <= 0
            ) {
                throw new RuntimeException(
                    'Item contabilístico sem valor na posição '
                        . ($indice + 1)
                        . '.'
                );
            }

            if (
                $debito > 0
                && $credito > 0
            ) {
                throw new RuntimeException(
                    'O item contabilístico não pode possuir débito e crédito simultaneamente.'
                );
            }

            $totalDebito += $debito;
            $totalCredito += $credito;
        }

        $totalDebito =
            round($totalDebito, 2);

        $totalCredito =
            round($totalCredito, 2);

        if (
            abs(
                $totalDebito
                    - $totalCredito
            ) > 0.009
        ) {
            throw new RuntimeException(
                'O lançamento contabilístico automático está desbalanceado.'
            );
        }

        $numero =
            $this->reservarNumeroDiario(
                (int)$cabecalho['diario_id'],
                (int)$cabecalho['empresa_id']
            );

        $statusId =
            $this->statusLancamentoId(
                'Contabilizado'
            );

        $stmt = $this->pdo->prepare("
            INSERT INTO lancamentos_contabilisticos
            (
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
            VALUES
            (
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
                :origem,
                :origem_id,
                :status_id,
                :total_debito,
                :total_credito,
                NOW(),
                :usuario_id,
                :criado_por,
                :atualizado_por
            )
        ");

        $stmt->execute([
            ':empresa_id' =>
            $cabecalho['empresa_id'],

            ':filial_id' =>
            $cabecalho['filial_id'],

            ':exercicio_id' =>
            $cabecalho['exercicio_id'],

            ':periodo_id' =>
            $cabecalho['periodo_id'],

            ':diario_id' =>
            $cabecalho['diario_id'],

            ':numero' =>
            $numero,

            ':numero_documento' =>
            $cabecalho['numero_documento'] ?? null,

            ':referencia' =>
            $cabecalho['referencia'] ?? null,

            ':data_lancamento' =>
            $cabecalho['data_lancamento'],

            ':data_documento' =>
            $cabecalho['data_documento'] ?? null,

            ':descricao' =>
            $cabecalho['descricao'],

            ':origem' =>
            strtoupper(
                (string)$cabecalho['origem']
            ),

            ':origem_id' =>
            $cabecalho['origem_id'],

            ':status_id' =>
            $statusId,

            ':total_debito' =>
            $totalDebito,

            ':total_credito' =>
            $totalCredito,

            ':usuario_id' =>
            $cabecalho['usuario_id'],

            ':criado_por' =>
            $cabecalho['usuario_id'],

            ':atualizado_por' =>
            $cabecalho['usuario_id']
        ]);

        $lancamentoId =
            (int)$this->pdo
                ->lastInsertId();

        $stmtItem =
            $this->pdo->prepare("
                INSERT INTO lancamento_contabilistico_itens
                (
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
                VALUES
                (
                    :lancamento_id,
                    :conta_contabil_id,
                    :centro_custo_id,
                    :cliente_id,
                    :fornecedor_id,
                    :descricao,
                    :debito,
                    :credito,
                    :ordem
                )
            ");

        foreach ($itens as $indice => $item) {
            $stmtItem->execute([
                ':lancamento_id' =>
                $lancamentoId,

                ':conta_contabil_id' =>
                $item['conta_contabil_id'],

                ':centro_custo_id' =>
                $item['centro_custo_id'] ?? null,

                ':cliente_id' =>
                $item['cliente_id'] ?? null,

                ':fornecedor_id' =>
                $item['fornecedor_id'] ?? null,

                ':descricao' =>
                $item['descricao'] ?? null,

                ':debito' =>
                round(
                    (float)(
                        $item['debito']
                        ?? 0
                    ),
                    2
                ),

                ':credito' =>
                round(
                    (float)(
                        $item['credito']
                        ?? 0
                    ),
                    2
                ),

                ':ordem' =>
                $indice + 1
            ]);
        }

        return $lancamentoId;
    }

    private function validarData(
        string $data
    ): void {

        $objeto =
            DateTime::createFromFormat(
                'Y-m-d',
                $data
            );

        if (
            !$objeto
            || $objeto->format('Y-m-d')
            !== $data
        ) {
            throw new RuntimeException(
                'Data contabilística inválida.'
            );
        }
    }

    // ==================================================
    // CONTABILIZAR RECEBIMENTO DE CLIENTE
    // ==================================================

    public function contabilizarRecebimento(
        array $dados
    ): ?int {

        $empresaId =
            (int)(
                $dados['empresa_id']
                ?? 0
            );

        $filialId =
            !empty($dados['filial_id'])
            ? (int)$dados['filial_id']
            : null;

        $centroCustoId =
            !empty($dados['centro_custo_id'])
            ? (int)$dados['centro_custo_id']
            : null;

        $recebimentoId =
            (int)(
                $dados['recebimento_id']
                ?? 0
            );

        $contaReceberId =
            (int)(
                $dados['conta_receber_id']
                ?? 0
            );

        $clienteId =
            (int)(
                $dados['cliente_id']
                ?? 0
            );

        $usuarioId =
            (int)(
                $dados['usuario_id']
                ?? 0
            );

        $valor =
            round(
                (float)(
                    $dados['valor']
                    ?? 0
                ),
                2
            );

        $data =
            trim(
                (string)(
                    $dados['data']
                    ?? ''
                )
            );

        $destino =
            strtoupper(
                trim(
                    (string)(
                        $dados['destino']
                        ?? ''
                    )
                )
            );

        $numeroDocumento =
            isset(
                $dados['numero_documento']
            )
            ? trim(
                (string)$dados['numero_documento']
            )
            : '';

        $descricao =
            trim(
                (string)(
                    $dados['descricao']
                    ?? ''
                )
            );

        // ==========================================
        // VALIDAÇÕES
        // ==========================================

        if ($empresaId <= 0) {
            throw new RuntimeException(
                'Empresa inválida para contabilização do recebimento.'
            );
        }

        if ($recebimentoId <= 0) {
            throw new RuntimeException(
                'Recebimento inválido para contabilização.'
            );
        }

        if ($contaReceberId <= 0) {
            throw new RuntimeException(
                'Conta a receber inválida para contabilização.'
            );
        }

        if ($clienteId <= 0) {
            throw new RuntimeException(
                'Cliente inválido para contabilização do recebimento.'
            );
        }

        if ($usuarioId <= 0) {
            throw new RuntimeException(
                'Utilizador inválido para contabilização do recebimento.'
            );
        }

        if ($valor <= 0) {
            throw new RuntimeException(
                'Valor inválido para contabilização do recebimento.'
            );
        }

        $this->validarData(
            $data
        );

        if (
            !in_array(
                $destino,
                [
                    'CAIXA',
                    'BANCO'
                ],
                true
            )
        ) {
            throw new RuntimeException(
                'Destino inválido para contabilização do recebimento.'
            );
        }

        /*
     * Impede a criação de dois lançamentos para
     * o mesmo registo da tabela recebimentos.
     */
        if (
            $this->existeLancamentoOrigem(
                $empresaId,
                $filialId,
                'RECEBIMENTO',
                $recebimentoId
            )
        ) {
            return null;
        }

        // ==========================================
        // CONTEXTO CONTABILÍSTICO
        // ==========================================

        $contexto =
            $this->contextoData(
                $empresaId,
                $data
            );

        $tipoDiario =
            $destino === 'CAIXA'
            ? 'CAIXA'
            : 'BANCOS';

        $diario =
            $this->diarioPorTipo(
                $empresaId,
                $tipoDiario
            );

        // ==========================================
        // CONTAS DE INTEGRAÇÃO
        // ==========================================

        $contaClientes =
            $this->contaIntegracao(
                $empresaId,
                'CLIENTES'
            );

        $codigoDestino =
            $destino === 'CAIXA'
            ? 'CAIXA'
            : 'BANCOS';

        $contaDestino =
            $this->contaIntegracao(
                $empresaId,
                $codigoDestino
            );

        if ($numeroDocumento === '') {
            $numeroDocumento =
                'REC-' . $recebimentoId;
        }

        if ($descricao === '') {
            $descricao =
                'Recebimento da conta a receber #'
                . $contaReceberId;
        }

        // ==========================================
        // CRIAR LANÇAMENTO
        // ==========================================

        return $this->criarLancamento(
            [
                'empresa_id' =>
                $empresaId,

                'filial_id' =>
                $filialId,

                'exercicio_id' =>
                (int)$contexto['exercicio_id'],

                'periodo_id' =>
                (int)$contexto['periodo_id'],

                'diario_id' =>
                (int)$diario['id'],

                'numero_documento' =>
                $numeroDocumento,

                'referencia' =>
                'RECEBIMENTO-'
                    . $recebimentoId,

                'data_lancamento' =>
                $data,

                'data_documento' =>
                $data,

                'descricao' =>
                $descricao,

                'origem' =>
                'RECEBIMENTO',

                'origem_id' =>
                $recebimentoId,

                'usuario_id' =>
                $usuarioId
            ],
            [
                /*
             * Débito:
             * aumenta Caixa ou Bancos.
             */
                [
                    'conta_contabil_id' =>
                    (int)$contaDestino['id'],

                    'centro_custo_id' =>
                    $centroCustoId,

                    'cliente_id' =>
                    null,

                    'fornecedor_id' =>
                    null,

                    'descricao' =>
                    'Entrada do recebimento #'
                        . $recebimentoId,

                    'debito' =>
                    $valor,

                    'credito' =>
                    0
                ],

                /*
             * Crédito:
             * reduz a dívida do cliente.
             */
                [
                    'conta_contabil_id' =>
                    (int)$contaClientes['id'],

                    'centro_custo_id' =>
                    null,

                    'cliente_id' =>
                    $clienteId,

                    'fornecedor_id' =>
                    null,

                    'descricao' =>
                    'Liquidação da conta do cliente #'
                        . $contaReceberId,

                    'debito' =>
                    0,

                    'credito' =>
                    $valor
                ]
            ]
        );
    }
}
