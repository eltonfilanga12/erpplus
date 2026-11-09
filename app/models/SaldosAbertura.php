<?php

declare(strict_types=1);

final class SaldosAbertura
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    // ==================================================
    // DADOS INICIAIS
    // ==================================================

    public function dadosIniciais(
        int $empresaId
    ): array {

        return [
            'exercicios' =>
            $this->listarExercicios(
                $empresaId
            ),

            'planos' =>
            $this->listarPlanos(
                $empresaId
            ),

            'filiais' =>
            $this->listarFiliais(
                $empresaId
            ),

            'contas' =>
            $this->listarContas(
                $empresaId
            )
        ];
    }

    // ==================================================
    // EXERCÍCIOS
    // ==================================================

    public function listarExercicios(
        int $empresaId
    ): array {

        $sql = "
        SELECT
            e.id,
            e.codigo,
            e.descricao,
            e.data_inicio,
            e.data_fim,
            e.status_id

        FROM exercicios_contabilisticos e

        WHERE e.empresa_id = :empresa_id

        ORDER BY
            e.data_inicio DESC
    ";

        $stmt =
            $this->pdo->prepare(
                $sql
            );

        $stmt->execute([
            ':empresa_id' =>
            $empresaId
        ]);

        return $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );
    }

    // ==================================================
    // PLANOS DE CONTAS
    // ==================================================

    public function listarPlanos(
        int $empresaId
    ): array {

        $sql = "
            SELECT
                p.id,
                p.codigo,
                p.nome,
                p.padrao,
                p.status_id

            FROM planos_contas p

            WHERE p.empresa_id = :empresa_id

            ORDER BY
                p.padrao DESC,
                p.nome
        ";

        $stmt =
            $this->pdo->prepare(
                $sql
            );

        $stmt->execute([
            ':empresa_id' =>
            $empresaId
        ]);

        return
            $stmt->fetchAll(
                PDO::FETCH_ASSOC
            );
    }

    // ==================================================
    // FILIAIS
    // ==================================================

    public function listarFiliais(
        int $empresaId
    ): array {

        $sql = "
            SELECT
                f.id,
                f.codigo,
                f.nome

            FROM filiais f

            WHERE f.empresa_id = :empresa_id

            ORDER BY f.nome
        ";

        $stmt =
            $this->pdo->prepare(
                $sql
            );

        $stmt->execute([
            ':empresa_id' =>
            $empresaId
        ]);

        return
            $stmt->fetchAll(
                PDO::FETCH_ASSOC
            );
    }

    // ==================================================
    // CONTAS ANALÍTICAS
    // ==================================================

    public function listarContas(
        int $empresaId
    ): array {

        $sql = "
            SELECT
                c.id,
                c.plano_contas_id,
                c.codigo,
                c.nome,
                c.grupo,
                c.natureza,
                c.tipo,
                c.aceita_lancamento,
                c.integracao_codigo

            FROM contas_contabilisticas c

            WHERE c.empresa_id = :empresa_id
              AND c.tipo = 'ANALITICA'
              AND c.aceita_lancamento = 1

            ORDER BY c.codigo
        ";

        $stmt =
            $this->pdo->prepare(
                $sql
            );

        $stmt->execute([
            ':empresa_id' =>
            $empresaId
        ]);

        return
            $stmt->fetchAll(
                PDO::FETCH_ASSOC
            );
    }

    // ==================================================
    // LISTAR ABERTURAS
    // ==================================================

    public function listar(
        int $empresaId
    ): array {

        $sql = "
            SELECT
                sa.id,
                sa.data_abertura,
                sa.descricao,

                sa.total_debito,
                sa.total_credito,

                sa.status_id,
                s.nome AS status_nome,

                sa.lancamento_contabil_id,

                e.codigo AS exercicio_codigo,

                pc.codigo AS plano_codigo,
                pc.nome AS plano_nome,

                f.codigo AS filial_codigo,
                f.nome AS filial_nome,

                sa.criado_em,
                sa.confirmado_em

            FROM saldos_abertura sa

            INNER JOIN status s
                ON s.id = sa.status_id

            INNER JOIN exercicios_contabilisticos e
                ON e.id = sa.exercicio_id

            INNER JOIN planos_contas pc
                ON pc.id = sa.plano_contas_id

            LEFT JOIN filiais f
                ON f.id = sa.filial_id

            WHERE sa.empresa_id = :empresa_id

            ORDER BY
                sa.data_abertura DESC,
                sa.id DESC
        ";

        $stmt =
            $this->pdo->prepare(
                $sql
            );

        $stmt->execute([
            ':empresa_id' =>
            $empresaId
        ]);

        return
            $stmt->fetchAll(
                PDO::FETCH_ASSOC
            );
    }

    // ==================================================
    // BUSCAR ABERTURA
    // ==================================================

    public function buscar(
        int $empresaId,
        int $id
    ): array {

        $sql = "
            SELECT
                sa.*

            FROM saldos_abertura sa

            WHERE sa.id = :id
              AND sa.empresa_id = :empresa_id

            LIMIT 1
        ";

        $stmt =
            $this->pdo->prepare(
                $sql
            );

        $stmt->execute([
            ':id' =>
            $id,

            ':empresa_id' =>
            $empresaId
        ]);

        $abertura =
            $stmt->fetch(
                PDO::FETCH_ASSOC
            );

        if (!$abertura) {
            throw new RuntimeException(
                'Saldo de abertura não encontrado.'
            );
        }

        $sqlItens = "
            SELECT
                i.id,
                i.conta_contabil_id,
                i.centro_custo_id,
                i.descricao,
                i.debito,
                i.credito,
                i.origem,
                i.referencia_id,
                i.ordem,

                c.codigo AS conta_codigo,
                c.nome AS conta_nome,
                c.grupo AS conta_grupo

            FROM saldos_abertura_itens i

            INNER JOIN contas_contabilisticas c
                ON c.id = i.conta_contabil_id

            WHERE i.saldo_abertura_id =
                :saldo_abertura_id

            ORDER BY
                i.ordem,
                i.id
        ";

        $stmtItens =
            $this->pdo->prepare(
                $sqlItens
            );

        $stmtItens->execute([
            ':saldo_abertura_id' =>
            $id
        ]);

        $abertura['itens'] =
            $stmtItens->fetchAll(
                PDO::FETCH_ASSOC
            );

        return $abertura;
    }

    // ==================================================
    // GUARDAR RASCUNHO
    // ==================================================

    public function guardarRascunho(
        int $empresaId,
        ?int $id,
        int $exercicioId,
        int $planoContasId,
        ?int $filialId,
        string $dataAbertura,
        ?string $descricao,
        array $itens,
        ?int $usuarioId
    ): int {

        $this->validarCabecalho(
            $empresaId,
            $exercicioId,
            $planoContasId,
            $filialId,
            $dataAbertura
        );

        $itens =
            $this->normalizarItens(
                $empresaId,
                $planoContasId,
                $itens
            );

        $totais =
            $this->calcularTotais(
                $itens
            );

        $statusRascunho =
            $this->statusId(
                'Rascunho'
            );

        $this->pdo->beginTransaction();

        try {

            if ($id !== null) {

                $atual =
                    $this->buscar(
                        $empresaId,
                        $id
                    );

                if (
                    (int)$atual['status_id']
                    !== $statusRascunho
                ) {
                    throw new RuntimeException(
                        'Somente uma abertura em rascunho pode ser alterada.'
                    );
                }

                $sql = "
                    UPDATE saldos_abertura

                    SET
                        filial_id = :filial_id,
                        exercicio_id = :exercicio_id,
                        plano_contas_id = :plano_contas_id,
                        data_abertura = :data_abertura,
                        descricao = :descricao,
                        total_debito = :total_debito,
                        total_credito = :total_credito,
                        atualizado_por = :atualizado_por

                    WHERE id = :id
                      AND empresa_id = :empresa_id
                ";

                $stmt =
                    $this->pdo->prepare(
                        $sql
                    );

                $stmt->execute([
                    ':filial_id' =>
                    $filialId,

                    ':exercicio_id' =>
                    $exercicioId,

                    ':plano_contas_id' =>
                    $planoContasId,

                    ':data_abertura' =>
                    $dataAbertura,

                    ':descricao' =>
                    $descricao,

                    ':total_debito' =>
                    $totais['debito'],

                    ':total_credito' =>
                    $totais['credito'],

                    ':atualizado_por' =>
                    $usuarioId,

                    ':id' =>
                    $id,

                    ':empresa_id' =>
                    $empresaId
                ]);

                $stmt =
                    $this->pdo->prepare(
                        "
                        DELETE FROM
                            saldos_abertura_itens

                        WHERE saldo_abertura_id = ?
                        "
                    );

                $stmt->execute([
                    $id
                ]);
            } else {

                $sql = "
                    INSERT INTO saldos_abertura
                    (
                        empresa_id,
                        filial_id,
                        exercicio_id,
                        plano_contas_id,
                        data_abertura,
                        descricao,
                        status_id,
                        total_debito,
                        total_credito,
                        criado_por,
                        atualizado_por
                    )
                    VALUES
                    (
                        :empresa_id,
                        :filial_id,
                        :exercicio_id,
                        :plano_contas_id,
                        :data_abertura,
                        :descricao,
                        :status_id,
                        :total_debito,
                        :total_credito,
                        :criado_por,
                        :atualizado_por
                    )
                ";

                $stmt =
                    $this->pdo->prepare(
                        $sql
                    );

                $stmt->execute([
                    ':empresa_id' =>
                    $empresaId,

                    ':filial_id' =>
                    $filialId,

                    ':exercicio_id' =>
                    $exercicioId,

                    ':plano_contas_id' =>
                    $planoContasId,

                    ':data_abertura' =>
                    $dataAbertura,

                    ':descricao' =>
                    $descricao,

                    ':status_id' =>
                    $statusRascunho,

                    ':total_debito' =>
                    $totais['debito'],

                    ':total_credito' =>
                    $totais['credito'],

                    ':criado_por' =>
                    $usuarioId,

                    ':atualizado_por' =>
                    $usuarioId
                ]);

                $id =
                    (int)$this->pdo
                        ->lastInsertId();
            }

            $this->inserirItens(
                $id,
                $itens
            );

            $this->pdo->commit();

            return $id;
        } catch (Throwable $e) {

            if (
                $this->pdo
                ->inTransaction()
            ) {
                $this->pdo
                    ->rollBack();
            }

            throw $e;
        }
    }

    // ==================================================
    // INSERIR ITENS
    // ==================================================

    private function inserirItens(
        int $saldoAberturaId,
        array $itens
    ): void {

        $sql = "
            INSERT INTO saldos_abertura_itens
            (
                saldo_abertura_id,
                conta_contabil_id,
                centro_custo_id,
                descricao,
                debito,
                credito,
                origem,
                referencia_id,
                ordem
            )
            VALUES
            (
                :saldo_abertura_id,
                :conta_contabil_id,
                :centro_custo_id,
                :descricao,
                :debito,
                :credito,
                :origem,
                :referencia_id,
                :ordem
            )
        ";

        $stmt =
            $this->pdo->prepare(
                $sql
            );

        foreach (
            $itens as $ordem => $item
        ) {

            $stmt->execute([
                ':saldo_abertura_id' =>
                $saldoAberturaId,

                ':conta_contabil_id' =>
                $item['conta_contabil_id'],

                ':centro_custo_id' =>
                $item['centro_custo_id'],

                ':descricao' =>
                $item['descricao'],

                ':debito' =>
                $item['debito'],

                ':credito' =>
                $item['credito'],

                ':origem' =>
                $item['origem'],

                ':referencia_id' =>
                $item['referencia_id'],

                ':ordem' =>
                $ordem + 1
            ]);
        }
    }

    // ==================================================
    // CALCULAR STOCK NA DATA DE ABERTURA
    // ==================================================

    public function calcularStockAbertura(
        int $empresaId,
        string $dataAbertura,
        ?int $filialId = null
    ): array {

        if (
            !$this->dataValida(
                $dataAbertura
            )
        ) {
            throw new RuntimeException(
                'Data de abertura inválida.'
            );
        }

        /*
         * estoque_saldos representa o saldo ATUAL.
         *
         * Para descobrir o saldo existente no início
         * da data de abertura:
         *
         * saldo_abertura =
         * saldo_atual
         * - entradas posteriores
         * + saídas posteriores
         * +/- ajustes posteriores
         *
         * Em vez de tentar interpretar somente valores,
         * reconstruímos quantidade por produto/armazém
         * a partir do saldo atual e dos movimentos
         * ocorridos desde a data de abertura.
         */

        $params = [
            ':empresa_id' =>
            $empresaId,

            ':data_abertura' =>
            $dataAbertura . ' 00:00:00'
        ];

        $filtroFilialSaldo = '';
        $filtroFilialMovimento = '';

        /*
         * Se posteriormente quisermos restringir
         * por filial, precisamos relacionar armazéns
         * à filial.
         */
        if ($filialId !== null) {

            $filtroFilialSaldo = "
                AND EXISTS
                (
                    SELECT 1
                    FROM armazens a
                    WHERE a.id = es.armazem_id
                      AND a.filial_id = :filial_id
                )
            ";

            $filtroFilialMovimento = "
                AND
                (
                    EXISTS
                    (
                        SELECT 1
                        FROM armazens ao
                        WHERE ao.id = m.armazem_origem_id
                          AND ao.filial_id = :filial_id
                    )

                    OR

                    EXISTS
                    (
                        SELECT 1
                        FROM armazens ad
                        WHERE ad.id = m.armazem_destino_id
                          AND ad.filial_id = :filial_id
                    )
                )
            ";

            $params[':filial_id'] =
                $filialId;
        }

        // ----------------------------------------------
        // SALDO ATUAL
        // ----------------------------------------------

        $sqlSaldo = "
            SELECT
                es.armazem_id,
                es.produto_id,
                es.quantidade,
                es.custo_medio

            FROM estoque_saldos es

            WHERE es.empresa_id = :empresa_id

            {$filtroFilialSaldo}
        ";

        $stmt =
            $this->pdo->prepare(
                $sqlSaldo
            );

        $paramsSaldo = [
            ':empresa_id' =>
            $empresaId
        ];

        if ($filialId !== null) {
            $paramsSaldo[':filial_id'] =
                $filialId;
        }

        $stmt->execute(
            $paramsSaldo
        );

        $saldos = [];

        foreach (
            $stmt->fetchAll(
                PDO::FETCH_ASSOC
            ) as $row
        ) {

            $chave =
                (int)$row['armazem_id']
                . ':'
                . (int)$row['produto_id'];

            $saldos[$chave] = [
                'armazem_id' =>
                (int)$row['armazem_id'],

                'produto_id' =>
                (int)$row['produto_id'],

                'quantidade' =>
                (float)$row['quantidade'],

                'custo_medio' =>
                (float)$row['custo_medio']
            ];
        }

        // ----------------------------------------------
        // MOVIMENTOS POSTERIORES
        // ----------------------------------------------

        $sqlMovimentos = "
            SELECT
                m.tipo,
                m.armazem_origem_id,
                m.armazem_destino_id,

                i.produto_id,
                i.quantidade,
                i.custo_unitario

            FROM movimentos_estoque m

            INNER JOIN movimento_estoque_itens i
                ON i.movimento_id = m.id

            WHERE m.empresa_id = :empresa_id

              AND m.data_movimento >=
                    :data_abertura

              {$filtroFilialMovimento}

            ORDER BY
                m.data_movimento DESC,
                m.id DESC,
                i.id DESC
        ";

        $stmt =
            $this->pdo->prepare(
                $sqlMovimentos
            );

        $stmt->execute(
            $params
        );

        $movimentos =
            $stmt->fetchAll(
                PDO::FETCH_ASSOC
            );

        /*
         * Reverte as quantidades posteriores.
         */
        foreach ($movimentos as $movimento) {

            $tipo =
                strtoupper(
                    (string)$movimento['tipo']
                );

            $produtoId =
                (int)$movimento['produto_id'];

            $quantidade =
                (float)$movimento['quantidade'];

            $origemId =
                !empty($movimento['armazem_origem_id'])
                ? (int)$movimento['armazem_origem_id']
                : null;

            $destinoId =
                !empty($movimento['armazem_destino_id'])
                ? (int)$movimento['armazem_destino_id']
                : null;

            if (
                $tipo === 'ENTRADA'
                && $destinoId !== null
            ) {

                $this->ajustarQuantidadeReconstruida(
                    $saldos,
                    $destinoId,
                    $produtoId,
                    -$quantidade,
                    (float)$movimento['custo_unitario']
                );
            } elseif (
                $tipo === 'SAIDA'
                && $origemId !== null
            ) {

                $this->ajustarQuantidadeReconstruida(
                    $saldos,
                    $origemId,
                    $produtoId,
                    $quantidade,
                    (float)$movimento['custo_unitario']
                );
            } elseif (
                $tipo === 'TRANSFERENCIA'
            ) {

                if ($origemId !== null) {

                    $this->ajustarQuantidadeReconstruida(
                        $saldos,
                        $origemId,
                        $produtoId,
                        $quantidade,
                        (float)$movimento['custo_unitario']
                    );
                }

                if ($destinoId !== null) {

                    $this->ajustarQuantidadeReconstruida(
                        $saldos,
                        $destinoId,
                        $produtoId,
                        -$quantidade,
                        (float)$movimento['custo_unitario']
                    );
                }
            } elseif (
                $tipo === 'AJUSTE'
            ) {

                /*
                 * No teu banco existem ajustes com
                 * quantidade positiva e negativa.
                 *
                 * O valor do item representa a variação
                 * aplicada ao saldo.
                 */
                $armazemId =
                    $destinoId
                    ?? $origemId;

                if ($armazemId !== null) {

                    $this->ajustarQuantidadeReconstruida(
                        $saldos,
                        $armazemId,
                        $produtoId,
                        -$quantidade,
                        (float)$movimento['custo_unitario']
                    );
                }
            }
        }

        // ----------------------------------------------
        // TOTALIZAÇÃO
        // ----------------------------------------------

        $produtos = [];
        $total = 0.00;

        foreach ($saldos as $saldo) {

            $quantidade =
                round(
                    (float)$saldo['quantidade'],
                    3
                );

            if ($quantidade <= 0) {
                continue;
            }

            $custo =
                (float)$saldo['custo_medio'];

            $valor =
                round(
                    $quantidade * $custo,
                    2
                );

            $total += $valor;

            $produtos[] = [
                'armazem_id' =>
                $saldo['armazem_id'],

                'produto_id' =>
                $saldo['produto_id'],

                'quantidade' =>
                $quantidade,

                'custo_unitario' =>
                $custo,

                'valor' =>
                $valor
            ];
        }

        return [
            'data_abertura' =>
            $dataAbertura,

            'quantidade_produtos' =>
            count($produtos),

            'valor_total' =>
            round(
                $total,
                2
            ),

            'produtos' =>
            $produtos
        ];
    }

    // ==================================================
    // AJUSTAR QUANTIDADE RECONSTRUÍDA
    // ==================================================

    private function ajustarQuantidadeReconstruida(
        array &$saldos,
        int $armazemId,
        int $produtoId,
        float $variacao,
        float $custo
    ): void {

        $chave =
            $armazemId
            . ':'
            . $produtoId;

        if (
            !isset(
                $saldos[$chave]
            )
        ) {
            $saldos[$chave] = [
                'armazem_id' =>
                $armazemId,

                'produto_id' =>
                $produtoId,

                'quantidade' =>
                0.0,

                'custo_medio' =>
                $custo
            ];
        }

        $saldos[$chave]['quantidade'] +=
            $variacao;

        /*
         * Se o produto não existia no saldo atual,
         * utiliza o custo do movimento como referência.
         */
        if (
            (float)$saldos[$chave]['custo_medio']
            <= 0
            && $custo > 0
        ) {
            $saldos[$chave]['custo_medio'] =
                $custo;
        }
    }

    // ==================================================
    // IMPORTAR STOCK PARA A ABERTURA
    // ==================================================

    public function itemStockAbertura(
        int $empresaId,
        int $planoContasId,
        string $dataAbertura,
        ?int $filialId = null
    ): array {

        $stock =
            $this->calcularStockAbertura(
                $empresaId,
                $dataAbertura,
                $filialId
            );

        if (
            $stock['valor_total']
            <= 0
        ) {
            throw new RuntimeException(
                'Não foi encontrado stock para importar na data de abertura.'
            );
        }

        $conta =
            $this->contaIntegracao(
                $empresaId,
                $planoContasId,
                'STOCK'
            );

        return [
            'conta_contabil_id' =>
            (int)$conta['id'],

            'conta_codigo' =>
            $conta['codigo'],

            'conta_nome' =>
            $conta['nome'],

            'centro_custo_id' =>
            null,

            'descricao' =>
            'Saldo inicial de mercadorias em armazém',

            'debito' =>
            $stock['valor_total'],

            'credito' =>
            0.00,

            'origem' =>
            'STOCK',

            'referencia_id' =>
            null,

            'stock' =>
            $stock
        ];
    }

    // ==================================================
    // CONFIRMAR ABERTURA
    // ==================================================

    public function confirmar(
        int $empresaId,
        int $id,
        ?int $usuarioId
    ): array {

        $abertura =
            $this->buscar(
                $empresaId,
                $id
            );

        $statusRascunho =
            $this->statusId(
                'Rascunho'
            );

        if (
            (int)$abertura['status_id']
            !== $statusRascunho
        ) {
            throw new RuntimeException(
                'Somente uma abertura em rascunho pode ser confirmada.'
            );
        }

        if (
            !empty($abertura['lancamento_contabil_id'])
        ) {
            throw new RuntimeException(
                'Esta abertura já possui lançamento contabilístico.'
            );
        }

        if (
            count(
                $abertura['itens']
            ) < 2
        ) {
            throw new RuntimeException(
                'A abertura deve possuir pelo menos dois itens.'
            );
        }

        $totaisRecalculados =
            $this->calcularTotais(
                $abertura['itens']
            );


        $totalDebito =
            $totaisRecalculados['debito'];


        $totalCredito =
            $totaisRecalculados['credito'];

        if (
            $totalDebito <= 0
            || $totalCredito <= 0
        ) {
            throw new RuntimeException(
                'A abertura deve possuir valores a débito e a crédito.'
            );
        }

        if (
            abs(
                $totalDebito
                    - $totalCredito
            ) > 0.009
        ) {
            throw new RuntimeException(
                'A abertura não está equilibrada. O total de débito deve ser igual ao total de crédito.'
            );
        }

        $periodo =
            $this->periodoDaData(
                $empresaId,
                (int)$abertura['exercicio_id'],
                (string)$abertura['data_abertura']
            );

        $diario =
            $this->diarioAbertura(
                $empresaId
            );

        $statusContabilizado =
            $this->statusLancamentoContabilizado();

        $numero =
            $this->proximoNumeroDiario(
                $empresaId,
                (int)$diario['id']
            );

        $statusConfirmado =
            $this->statusId(
                'Confirmado'
            );

        $this->pdo->beginTransaction();

        try {

            // ------------------------------------------
            // LANÇAMENTO
            // ------------------------------------------

            $sql = "
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
                    'ABERTURA',
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

            $stmt =
                $this->pdo->prepare(
                    $sql
                );

            $stmt->execute([
                ':empresa_id' =>
                $empresaId,

                ':filial_id' =>
                $abertura['filial_id']
                    ?: null,

                ':exercicio_id' =>
                $abertura['exercicio_id'],

                ':periodo_id' =>
                $periodo['id'],

                ':diario_id' =>
                $diario['id'],

                ':numero' =>
                $numero,

                ':numero_documento' =>
                'ABE-'
                    . str_pad(
                        (string)$id,
                        6,
                        '0',
                        STR_PAD_LEFT
                    ),

                ':referencia' =>
                'ABERTURA-' . $id,

                ':data_lancamento' =>
                $abertura['data_abertura'],

                ':data_documento' =>
                $abertura['data_abertura'],

                ':descricao' =>
                $abertura['descricao']
                    ?: 'Saldos de abertura',

                ':origem_id' =>
                $id,

                ':status_id' =>
                $statusContabilizado,

                ':total_debito' =>
                $totalDebito,

                ':total_credito' =>
                $totalCredito,

                ':contabilizado_por' =>
                $usuarioId,

                ':criado_por' =>
                $usuarioId,

                ':atualizado_por' =>
                $usuarioId
            ]);

            $lancamentoId =
                (int)$this->pdo
                    ->lastInsertId();

            // ------------------------------------------
            // ITENS CONTABILÍSTICOS
            // ------------------------------------------

            $sqlItem = "
                INSERT INTO
                    lancamento_contabilistico_itens
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
                    NULL,
                    NULL,
                    :descricao,
                    :debito,
                    :credito,
                    :ordem
                )
            ";

            $stmtItem =
                $this->pdo->prepare(
                    $sqlItem
                );

            foreach (
                $abertura['itens']
                as $ordem => $item
            ) {

                $stmtItem->execute([
                    ':lancamento_id' =>
                    $lancamentoId,

                    ':conta_contabil_id' =>
                    $item['conta_contabil_id'],

                    ':centro_custo_id' =>
                    $item['centro_custo_id']
                        ?: null,

                    ':descricao' =>
                    $item['descricao'],

                    ':debito' =>
                    $item['debito'],

                    ':credito' =>
                    $item['credito'],

                    ':ordem' =>
                    $ordem + 1
                ]);
            }

            // ------------------------------------------
            // CONFIRMAR DOCUMENTO
            // ------------------------------------------

            $sql = "
                UPDATE saldos_abertura

                SET
                    status_id =
                        :status_id,

                    lancamento_contabil_id =
                        :lancamento_id,

                    confirmado_por =
                        :confirmado_por,

                    confirmado_em =
                        NOW(),

                    atualizado_por =
                        :atualizado_por

                WHERE id = :id
                  AND empresa_id = :empresa_id
            ";

            $stmt =
                $this->pdo->prepare(
                    $sql
                );

            $stmt->execute([
                ':status_id' =>
                $statusConfirmado,

                ':lancamento_id' =>
                $lancamentoId,

                ':confirmado_por' =>
                $usuarioId,

                ':atualizado_por' =>
                $usuarioId,

                ':id' =>
                $id,

                ':empresa_id' =>
                $empresaId
            ]);

            $this->pdo->commit();

            return [
                'id' =>
                $id,

                'lancamento_contabil_id' =>
                $lancamentoId,

                'numero_lancamento' =>
                $numero,

                'total_debito' =>
                $totalDebito,

                'total_credito' =>
                $totalCredito
            ];
        } catch (Throwable $e) {

            if (
                $this->pdo
                ->inTransaction()
            ) {
                $this->pdo
                    ->rollBack();
            }

            throw $e;
        }
    }

    public function listarCaixasDisponiveis(
        int $empresaId,
        string $dataAbertura,
        ?int $filialId = null
    ): array {

        $sql = "
        SELECT
            c.id,
            c.codigo,
            c.nome,
            c.filial_id,
            c.saldo_inicial,

            ROUND(
                c.saldo_inicial
                +
                COALESCE(
                    SUM(
                        CASE
                            WHEN mc.tipo IN (
                                'ENTRADA',
                                'SUPRIMENTO'
                            )
                            THEN mc.valor

                            WHEN mc.tipo IN (
                                'SAIDA',
                                'SANGRIA'
                            )
                            THEN -mc.valor

                            ELSE 0
                        END
                    ),
                    0
                ),
                2
            ) AS saldo_data

        FROM caixas c

        LEFT JOIN movimentos_caixa mc
            ON mc.caixa_id = c.id
            AND mc.empresa_id = c.empresa_id
            AND DATE(mc.data_movimento) < :data_abertura

        WHERE c.empresa_id = :empresa_id
    ";

        $params = [
            ':empresa_id' =>
            $empresaId,

            ':data_abertura' =>
            $dataAbertura
        ];


        if ($filialId !== null) {

            $sql .= "
            AND (
                c.filial_id = :filial_id
                OR c.filial_id IS NULL
            )
        ";

            $params[':filial_id'] =
                $filialId;
        }


        $sql .= "
        GROUP BY
            c.id,
            c.codigo,
            c.nome,
            c.filial_id,
            c.saldo_inicial

        HAVING saldo_data <> 0

        ORDER BY
            c.nome,
            c.id
    ";


        $stmt =
            $this->pdo->prepare(
                $sql
            );

        $stmt->execute(
            $params
        );


        return $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );
    }

    public function listarBancosDisponiveis(
        int $empresaId,
        string $dataAbertura,
        ?int $filialId = null
    ): array {

        $sql = "
        SELECT
            b.id,
            b.nome_banco,
            b.conta,
            b.tipo_conta,
            b.moeda,
            b.filial_id,
            b.saldo_inicial,

            ROUND(
                b.saldo_inicial
                +
                COALESCE(
                    SUM(
                        CASE
                            WHEN mb.tipo = 'ENTRADA'
                            THEN mb.valor

                            WHEN mb.tipo = 'SAIDA'
                            THEN -mb.valor

                            ELSE 0
                        END
                    ),
                    0
                ),
                2
            ) AS saldo_data

        FROM bancos b

        LEFT JOIN movimentos_bancarios mb
            ON mb.banco_id = b.id
            AND mb.empresa_id = b.empresa_id
            AND DATE(mb.data_movimento) < :data_abertura

        WHERE b.empresa_id = :empresa_id
    ";

        $params = [
            ':empresa_id' =>
            $empresaId,

            ':data_abertura' =>
            $dataAbertura
        ];


        if ($filialId !== null) {

            $sql .= "
            AND (
                b.filial_id = :filial_id
                OR b.filial_id IS NULL
            )
        ";

            $params[':filial_id'] =
                $filialId;
        }


        $sql .= "
        GROUP BY
            b.id,
            b.nome_banco,
            b.conta,
            b.tipo_conta,
            b.moeda,
            b.filial_id,
            b.saldo_inicial

        HAVING saldo_data <> 0

        ORDER BY
            b.nome_banco,
            b.id
    ";


        $stmt =
            $this->pdo->prepare(
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
    // IMPORTAR CAIXA PARA A ABERTURA
    // ==================================================

    public function itemCaixaAbertura(
        int $empresaId,
        int $planoContasId,
        int $caixaId,
        string $dataAbertura
    ): array {

        if (!$this->dataValida($dataAbertura)) {
            throw new RuntimeException(
                'Data de abertura inválida.'
            );
        }


        $sql = "
        SELECT
            c.id,
            c.codigo,
            c.nome,
            c.saldo_inicial,

            ROUND(
                c.saldo_inicial
                +
                COALESCE(
                    SUM(
                        CASE

                            WHEN mc.tipo IN (
                                'ENTRADA',
                                'SUPRIMENTO'
                            )
                            THEN mc.valor

                            WHEN mc.tipo IN (
                                'SAIDA',
                                'SANGRIA'
                            )
                            THEN -mc.valor

                            ELSE 0

                        END
                    ),
                    0
                ),
                2
            ) AS saldo_data

        FROM caixas c

        LEFT JOIN movimentos_caixa mc
            ON mc.caixa_id = c.id
           AND mc.empresa_id = c.empresa_id
           AND DATE(mc.data_movimento)
                < :data_abertura

        WHERE c.id = :caixa_id
          AND c.empresa_id = :empresa_id

        GROUP BY
            c.id,
            c.codigo,
            c.nome,
            c.saldo_inicial

        LIMIT 1
    ";


        $stmt =
            $this->pdo->prepare($sql);


        $stmt->execute([
            ':caixa_id' =>
            $caixaId,

            ':empresa_id' =>
            $empresaId,

            ':data_abertura' =>
            $dataAbertura
        ]);


        $caixa =
            $stmt->fetch(
                PDO::FETCH_ASSOC
            );


        if (!$caixa) {

            throw new RuntimeException(
                'Caixa não encontrado.'
            );
        }


        $saldo =
            round(
                (float)$caixa['saldo_data'],
                2
            );


        if ($saldo == 0.00) {

            throw new RuntimeException(
                'O caixa não possui saldo para importar na data de abertura.'
            );
        }


        $conta =
            $this->contaIntegracao(
                $empresaId,
                $planoContasId,
                'CAIXA'
            );


        return [
            'conta_contabil_id' =>
            (int)$conta['id'],

            'conta_codigo' =>
            $conta['codigo'],

            'conta_nome' =>
            $conta['nome'],

            'centro_custo_id' =>
            null,

            'descricao' =>
            'Saldo inicial do caixa '
                . (
                    $caixa['nome']
                    ?: $caixa['codigo']
                ),

            /*
         * Caixa normalmente é conta devedora.
         * Caso exista saldo credor anormal,
         * tratamos no lado oposto.
         */
            'debito' =>
            $saldo > 0
                ? $saldo
                : 0.00,

            'credito' =>
            $saldo < 0
                ? abs($saldo)
                : 0.00,

            'origem' =>
            'CAIXA',

            'referencia_id' =>
            $caixaId,

            'detalhes' => [
                'caixa_id' =>
                $caixaId,

                'codigo' =>
                $caixa['codigo'],

                'nome' =>
                $caixa['nome'],

                'saldo_data' =>
                $saldo
            ]
        ];
    }


    // ==================================================
    // IMPORTAR BANCO PARA A ABERTURA
    // ==================================================

    public function itemBancoAbertura(
        int $empresaId,
        int $planoContasId,
        int $bancoId,
        string $dataAbertura
    ): array {

        if (!$this->dataValida($dataAbertura)) {

            throw new RuntimeException(
                'Data de abertura inválida.'
            );
        }


        $sql = "
        SELECT
            b.id,
            b.nome_banco,
            b.conta,
            b.tipo_conta,
            b.moeda,
            b.saldo_inicial,

            ROUND(
                b.saldo_inicial
                +
                COALESCE(
                    SUM(
                        CASE

                            WHEN mb.tipo = 'ENTRADA'
                            THEN mb.valor

                            WHEN mb.tipo = 'SAIDA'
                            THEN -mb.valor

                            ELSE 0

                        END
                    ),
                    0
                ),
                2
            ) AS saldo_data

        FROM bancos b

        LEFT JOIN movimentos_bancarios mb
            ON mb.banco_id = b.id
           AND mb.empresa_id = b.empresa_id
           AND DATE(mb.data_movimento)
                < :data_abertura

        WHERE b.id = :banco_id
          AND b.empresa_id = :empresa_id

        GROUP BY
            b.id,
            b.nome_banco,
            b.conta,
            b.tipo_conta,
            b.moeda,
            b.saldo_inicial

        LIMIT 1
    ";


        $stmt =
            $this->pdo->prepare($sql);


        $stmt->execute([
            ':banco_id' =>
            $bancoId,

            ':empresa_id' =>
            $empresaId,

            ':data_abertura' =>
            $dataAbertura
        ]);


        $banco =
            $stmt->fetch(
                PDO::FETCH_ASSOC
            );


        if (!$banco) {

            throw new RuntimeException(
                'Conta bancária não encontrada.'
            );
        }


        $saldo =
            round(
                (float)$banco['saldo_data'],
                2
            );


        if ($saldo == 0.00) {

            throw new RuntimeException(
                'A conta bancária não possui saldo para importar na data de abertura.'
            );
        }


        /*
     * Se no teu plano o código de integração
     * for BANCO em vez de DEPOSITOS_ORDEM,
     * esta rotina tenta os dois.
     */
        try {

            $conta =
                $this->contaIntegracao(
                    $empresaId,
                    $planoContasId,
                    'BANCO'
                );
        } catch (Throwable $e) {

            $conta =
                $this->contaIntegracao(
                    $empresaId,
                    $planoContasId,
                    'DEPOSITOS_ORDEM'
                );
        }


        return [
            'conta_contabil_id' =>
            (int)$conta['id'],

            'conta_codigo' =>
            $conta['codigo'],

            'conta_nome' =>
            $conta['nome'],

            'centro_custo_id' =>
            null,

            'descricao' =>
            'Saldo inicial bancário — '
                . $banco['nome_banco'],

            'debito' =>
            $saldo > 0
                ? $saldo
                : 0.00,

            'credito' =>
            $saldo < 0
                ? abs($saldo)
                : 0.00,

            'origem' =>
            'BANCO',

            'referencia_id' =>
            $bancoId,

            'detalhes' => [
                'banco_id' =>
                $bancoId,

                'banco' =>
                $banco['nome_banco'],

                'conta' =>
                $banco['conta'],

                'moeda' =>
                $banco['moeda'],

                'saldo_data' =>
                $saldo
            ]
        ];
    }

    // ==================================================
    // CLIENTES DISPONÍVEIS PARA ABERTURA
    // ==================================================

    public function listarClientesDisponiveis(
        int $empresaId,
        string $dataAbertura,
        ?int $filialId = null
    ): array {

        if (!$this->dataValida($dataAbertura)) {
            throw new RuntimeException(
                'Data de abertura inválida.'
            );
        }

        $sql = "
        SELECT
            c.id,
            c.nome,
            c.nome_comercial,
            c.nuit,

            ROUND(
                SUM(cr.saldo),
                2
            ) AS saldo_data,

            COUNT(cr.id) AS documentos

        FROM contas_receber cr

        INNER JOIN clientes c
            ON c.id = cr.cliente_id
           AND c.empresa_id = cr.empresa_id

        WHERE cr.empresa_id = :empresa_id

          /*
           * Só documentos anteriores
           * à data de abertura.
           */
          AND cr.data_emissao < :data_abertura

          /*
           * Apenas valores ainda em dívida.
           */
          AND cr.saldo > 0
    ";

        $params = [
            ':empresa_id' =>
            $empresaId,

            ':data_abertura' =>
            $dataAbertura
        ];

        if ($filialId !== null) {

            $sql .= "
            AND (
                cr.filial_id = :filial_id
                OR cr.filial_id IS NULL
            )
        ";

            $params[':filial_id'] =
                $filialId;
        }

        $sql .= "
        GROUP BY
            c.id,
            c.nome,
            c.nome_comercial,
            c.nuit

        HAVING saldo_data > 0

        ORDER BY
            c.nome
    ";

        $stmt =
            $this->pdo->prepare(
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
    // FORNECEDORES DISPONÍVEIS PARA ABERTURA
    // ==================================================

    public function listarFornecedoresDisponiveis(
        int $empresaId,
        string $dataAbertura,
        ?int $filialId = null
    ): array {

        if (!$this->dataValida($dataAbertura)) {
            throw new RuntimeException(
                'Data de abertura inválida.'
            );
        }

        $sql = "
        SELECT
            f.id,
            f.nome,
            f.nome_comercial,
            f.nuit,

            ROUND(
                SUM(cp.saldo),
                2
            ) AS saldo_data,

            COUNT(cp.id) AS documentos

        FROM contas_pagar cp

        INNER JOIN fornecedores f
            ON f.id = cp.fornecedor_id
           AND f.empresa_id = cp.empresa_id

        WHERE cp.empresa_id = :empresa_id

          AND cp.data_emissao < :data_abertura

          AND cp.saldo > 0
    ";

        $params = [
            ':empresa_id' =>
            $empresaId,

            ':data_abertura' =>
            $dataAbertura
        ];

        if ($filialId !== null) {

            $sql .= "
            AND (
                cp.filial_id = :filial_id
                OR cp.filial_id IS NULL
            )
        ";

            $params[':filial_id'] =
                $filialId;
        }

        $sql .= "
        GROUP BY
            f.id,
            f.nome,
            f.nome_comercial,
            f.nuit

        HAVING saldo_data > 0

        ORDER BY
            f.nome
    ";

        $stmt =
            $this->pdo->prepare(
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
    // IMPORTAR CLIENTE PARA A ABERTURA
    // ==================================================

    public function itemClienteAbertura(
        int $empresaId,
        int $planoContasId,
        int $clienteId,
        string $dataAbertura
    ): array {

        if (!$this->dataValida($dataAbertura)) {
            throw new RuntimeException(
                'Data de abertura inválida.'
            );
        }

        $sql = "
        SELECT
            c.id,
            c.nome,
            c.nome_comercial,
            c.nuit,

            ROUND(
                SUM(cr.saldo),
                2
            ) AS saldo_data,

            COUNT(cr.id) AS documentos

        FROM contas_receber cr

        INNER JOIN clientes c
            ON c.id = cr.cliente_id
           AND c.empresa_id = cr.empresa_id

        WHERE cr.empresa_id = :empresa_id
          AND cr.cliente_id = :cliente_id

          AND cr.data_emissao < :data_abertura

          AND cr.saldo > 0

        GROUP BY
            c.id,
            c.nome,
            c.nome_comercial,
            c.nuit

        LIMIT 1
    ";

        $stmt =
            $this->pdo->prepare($sql);

        $stmt->execute([
            ':empresa_id' =>
            $empresaId,

            ':cliente_id' =>
            $clienteId,

            ':data_abertura' =>
            $dataAbertura
        ]);

        $cliente =
            $stmt->fetch(
                PDO::FETCH_ASSOC
            );

        if (!$cliente) {
            throw new RuntimeException(
                'O cliente não possui saldo anterior à data de abertura.'
            );
        }

        $saldo =
            round(
                (float)$cliente['saldo_data'],
                2
            );

        if ($saldo <= 0) {
            throw new RuntimeException(
                'O cliente não possui saldo a receber para importar.'
            );
        }

        $conta =
            $this->contaIntegracao(
                $empresaId,
                $planoContasId,
                'CLIENTES'
            );

        return [
            'conta_contabil_id' =>
            (int)$conta['id'],

            'conta_codigo' =>
            $conta['codigo'],

            'conta_nome' =>
            $conta['nome'],

            'centro_custo_id' =>
            null,

            'descricao' =>
            'Saldo inicial a receber de '
                . $cliente['nome'],

            'debito' =>
            $saldo,

            'credito' =>
            0.00,

            'origem' =>
            'CLIENTE',

            'referencia_id' =>
            $clienteId,

            'detalhes' => [
                'cliente_id' =>
                $clienteId,

                'cliente' =>
                $cliente['nome'],

                'nuit' =>
                $cliente['nuit'],

                'documentos' =>
                (int)$cliente['documentos'],

                'saldo_data' =>
                $saldo
            ]
        ];
    }


    // ==================================================
    // IMPORTAR FORNECEDOR PARA A ABERTURA
    // ==================================================

    public function itemFornecedorAbertura(
        int $empresaId,
        int $planoContasId,
        int $fornecedorId,
        string $dataAbertura
    ): array {

        if (!$this->dataValida($dataAbertura)) {
            throw new RuntimeException(
                'Data de abertura inválida.'
            );
        }

        $sql = "
        SELECT
            f.id,
            f.nome,
            f.nome_comercial,
            f.nuit,

            ROUND(
                SUM(cp.saldo),
                2
            ) AS saldo_data,

            COUNT(cp.id) AS documentos

        FROM contas_pagar cp

        INNER JOIN fornecedores f
            ON f.id = cp.fornecedor_id
           AND f.empresa_id = cp.empresa_id

        WHERE cp.empresa_id = :empresa_id
          AND cp.fornecedor_id = :fornecedor_id

          AND cp.data_emissao < :data_abertura

          AND cp.saldo > 0

        GROUP BY
            f.id,
            f.nome,
            f.nome_comercial,
            f.nuit

        LIMIT 1
    ";

        $stmt =
            $this->pdo->prepare($sql);

        $stmt->execute([
            ':empresa_id' =>
            $empresaId,

            ':fornecedor_id' =>
            $fornecedorId,

            ':data_abertura' =>
            $dataAbertura
        ]);

        $fornecedor =
            $stmt->fetch(
                PDO::FETCH_ASSOC
            );

        if (!$fornecedor) {
            throw new RuntimeException(
                'O fornecedor não possui saldo anterior à data de abertura.'
            );
        }

        $saldo =
            round(
                (float)$fornecedor['saldo_data'],
                2
            );

        if ($saldo <= 0) {
            throw new RuntimeException(
                'O fornecedor não possui saldo a pagar para importar.'
            );
        }

        $conta =
            $this->contaIntegracao(
                $empresaId,
                $planoContasId,
                'FORNECEDORES'
            );

        return [
            'conta_contabil_id' =>
            (int)$conta['id'],

            'conta_codigo' =>
            $conta['codigo'],

            'conta_nome' =>
            $conta['nome'],

            'centro_custo_id' =>
            null,

            'descricao' =>
            'Saldo inicial a pagar a '
                . $fornecedor['nome'],

            'debito' =>
            0.00,

            'credito' =>
            $saldo,

            'origem' =>
            'FORNECEDOR',

            'referencia_id' =>
            $fornecedorId,

            'detalhes' => [
                'fornecedor_id' =>
                $fornecedorId,

                'fornecedor' =>
                $fornecedor['nome'],

                'nuit' =>
                $fornecedor['nuit'],

                'documentos' =>
                (int)$fornecedor['documentos'],

                'saldo_data' =>
                $saldo
            ]
        ];
    }

    // ==================================================
// ATIVOS DISPONÍVEIS PARA ABERTURA
// ==================================================

public function listarAtivosDisponiveis(
    int $empresaId,
    string $dataAbertura,
    ?int $filialId = null
): array {

    if (!$this->dataValida($dataAbertura)) {

        throw new RuntimeException(
            'Data de abertura inválida.'
        );
    }


    $sql = "
        SELECT
            a.id,
            a.codigo,
            a.tipo,
            a.descricao,
            a.marca,
            a.modelo,
            a.numero_serie,
            a.filial_id,
            a.conta_contabil_id,
            a.data_aquisicao,
            a.data_entrada_uso,
            a.valor_aquisicao,
            a.valor_residual,
            a.situacao,

            cc.codigo
                AS conta_codigo,

            cc.nome
                AS conta_nome,

            config.conta_acumulada_id,

            acumulada.codigo
                AS conta_acumulada_codigo,

            acumulada.nome
                AS conta_acumulada_nome,

            ROUND(
                COALESCE(
                    SUM(
                        CASE

                            /*
                             * Só depreciações/amortizações
                             * anteriores ao mês da abertura.
                             */
                            WHEN (
                                dep.ano
                                    < YEAR(:data_dep_ano)

                                OR (
                                    dep.ano
                                        = YEAR(:data_dep_ano2)

                                    AND dep.mes
                                        < MONTH(:data_dep_mes)
                                )
                            )

                            /*
                             * Processamento que não foi
                             * posteriormente estornado.
                             */
                            AND dep.lancamento_estorno_id
                                IS NULL

                            THEN dep.valor_periodo

                            ELSE 0

                        END
                    ),
                    0
                ),
                2
            ) AS depreciacao_acumulada,

            ROUND(
                GREATEST(
                    a.valor_residual,

                    a.valor_aquisicao
                    -
                    COALESCE(
                        SUM(
                            CASE

                                WHEN (
                                    dep.ano
                                        < YEAR(:data_liq_ano)

                                    OR (
                                        dep.ano
                                            = YEAR(:data_liq_ano2)

                                        AND dep.mes
                                            < MONTH(:data_liq_mes)
                                    )
                                )

                                AND dep.lancamento_estorno_id
                                    IS NULL

                                THEN dep.valor_periodo

                                ELSE 0

                            END
                        ),
                        0
                    )
                ),
                2
            ) AS valor_liquido

        FROM ativos_empresa a

        INNER JOIN contas_contabilisticas cc
            ON cc.id =
                a.conta_contabil_id

           AND cc.empresa_id =
                a.empresa_id


        LEFT JOIN ativos_configuracoes_contabeis config
            ON config.empresa_id =
                a.empresa_id

           AND config.conta_ativo_id =
                a.conta_contabil_id


        LEFT JOIN contas_contabilisticas acumulada
            ON acumulada.id =
                config.conta_acumulada_id

           AND acumulada.empresa_id =
                a.empresa_id


        LEFT JOIN ativos_depreciacoes dep
            ON dep.empresa_id =
                a.empresa_id

           AND dep.ativo_id =
                a.id


        WHERE a.empresa_id =
            :empresa_id

          /*
           * O bem precisa já existir
           * antes da abertura.
           */
          AND a.data_aquisicao
                < :data_aquisicao

          AND a.situacao NOT IN (
                'VENDIDO',
                'ABATIDO'
          )
    ";


    $params = [

        ':empresa_id' =>
            $empresaId,

        ':data_aquisicao' =>
            $dataAbertura,

        ':data_dep_ano' =>
            $dataAbertura,

        ':data_dep_ano2' =>
            $dataAbertura,

        ':data_dep_mes' =>
            $dataAbertura,

        ':data_liq_ano' =>
            $dataAbertura,

        ':data_liq_ano2' =>
            $dataAbertura,

        ':data_liq_mes' =>
            $dataAbertura
    ];


    if ($filialId !== null) {

        $sql .= "
            AND (
                a.filial_id = :filial_id
                OR a.filial_id IS NULL
            )
        ";

        $params[':filial_id'] =
            $filialId;
    }


    $sql .= "
        GROUP BY
            a.id,
            a.codigo,
            a.tipo,
            a.descricao,
            a.marca,
            a.modelo,
            a.numero_serie,
            a.filial_id,
            a.conta_contabil_id,
            a.data_aquisicao,
            a.data_entrada_uso,
            a.valor_aquisicao,
            a.valor_residual,
            a.situacao,
            cc.codigo,
            cc.nome,
            config.conta_acumulada_id,
            acumulada.codigo,
            acumulada.nome

        HAVING valor_liquido > 0

        ORDER BY
            a.codigo,
            a.id
    ";


    $stmt =
        $this->pdo->prepare(
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
// PREPARAR ATIVO PARA A ABERTURA
// ==================================================

public function itensAtivoAbertura(
    int $empresaId,
    int $planoContasId,
    int $ativoId,
    string $dataAbertura
): array {

    if (!$this->dataValida($dataAbertura)) {

        throw new RuntimeException(
            'Data de abertura inválida.'
        );
    }


    $sql = "
        SELECT
            a.id,
            a.codigo,
            a.tipo,
            a.descricao,
            a.marca,
            a.modelo,
            a.numero_serie,
            a.conta_contabil_id,
            a.data_aquisicao,
            a.valor_aquisicao,
            a.valor_residual,
            a.situacao,

            cc.codigo
                AS conta_codigo,

            cc.nome
                AS conta_nome,

            cc.plano_contas_id,

            config.conta_acumulada_id,

            acumulada.codigo
                AS acumulada_codigo,

            acumulada.nome
                AS acumulada_nome,

            ROUND(
                COALESCE(
                    SUM(
                        CASE

                            WHEN (
                                dep.ano
                                    < YEAR(:data_dep_ano)

                                OR (
                                    dep.ano
                                        = YEAR(:data_dep_ano2)

                                    AND dep.mes
                                        < MONTH(:data_dep_mes)
                                )
                            )

                            AND dep.lancamento_estorno_id
                                IS NULL

                            THEN dep.valor_periodo

                            ELSE 0

                        END
                    ),
                    0
                ),
                2
            ) AS depreciacao_acumulada

        FROM ativos_empresa a

        INNER JOIN contas_contabilisticas cc
            ON cc.id =
                a.conta_contabil_id

           AND cc.empresa_id =
                a.empresa_id


        LEFT JOIN ativos_configuracoes_contabeis config
            ON config.empresa_id =
                a.empresa_id

           AND config.conta_ativo_id =
                a.conta_contabil_id


        LEFT JOIN contas_contabilisticas acumulada
            ON acumulada.id =
                config.conta_acumulada_id

           AND acumulada.empresa_id =
                a.empresa_id


        LEFT JOIN ativos_depreciacoes dep
            ON dep.empresa_id =
                a.empresa_id

           AND dep.ativo_id =
                a.id


        WHERE a.id =
            :ativo_id

          AND a.empresa_id =
            :empresa_id

          AND a.data_aquisicao
                < :data_aquisicao

          AND a.situacao NOT IN (
                'VENDIDO',
                'ABATIDO'
          )

        GROUP BY
            a.id,
            a.codigo,
            a.tipo,
            a.descricao,
            a.marca,
            a.modelo,
            a.numero_serie,
            a.conta_contabil_id,
            a.data_aquisicao,
            a.valor_aquisicao,
            a.valor_residual,
            a.situacao,
            cc.codigo,
            cc.nome,
            cc.plano_contas_id,
            config.conta_acumulada_id,
            acumulada.codigo,
            acumulada.nome

        LIMIT 1
    ";


    $stmt =
        $this->pdo->prepare(
            $sql
        );


    $stmt->execute([

        ':ativo_id' =>
            $ativoId,

        ':empresa_id' =>
            $empresaId,

        ':data_aquisicao' =>
            $dataAbertura,

        ':data_dep_ano' =>
            $dataAbertura,

        ':data_dep_ano2' =>
            $dataAbertura,

        ':data_dep_mes' =>
            $dataAbertura
    ]);


    $ativo =
        $stmt->fetch(
            PDO::FETCH_ASSOC
        );


    if (!$ativo) {

        throw new RuntimeException(
            'O ativo não existe ou não é anterior à data de abertura.'
        );
    }


    if (
        (int)$ativo['plano_contas_id']
        !== $planoContasId
    ) {

        throw new RuntimeException(
            'A conta contabilística do ativo não pertence ao plano de contas selecionado.'
        );
    }


    $valorAquisicao =
        round(
            (float)$ativo['valor_aquisicao'],
            2
        );


    $depreciacaoAcumulada =
        round(
            (float)$ativo[
                'depreciacao_acumulada'
            ],
            2
        );


    if ($valorAquisicao <= 0) {

        throw new RuntimeException(
            'O ativo não possui valor de aquisição válido.'
        );
    }


    if (
        $depreciacaoAcumulada
        > $valorAquisicao
    ) {

        throw new RuntimeException(
            'A depreciação acumulada do ativo é superior ao valor de aquisição.'
        );
    }


    /*
     * Se existe depreciação histórica,
     * é obrigatório possuir a conta
     * acumulada configurada.
     */
    if (
        $depreciacaoAcumulada > 0
        && empty(
            $ativo['conta_acumulada_id']
        )
    ) {

        throw new RuntimeException(
            'O ativo possui depreciação/amortização acumulada, mas a respetiva conta acumulada não está configurada.'
        );
    }


    $itens = [];


    // ==============================================
    // 1. VALOR BRUTO DO ATIVO
    // ==============================================

    $itens[] = [

        'conta_contabil_id' =>
            (int)$ativo[
                'conta_contabil_id'
            ],

        'centro_custo_id' =>
            null,

        'descricao' =>
            'Saldo inicial do ativo '
            . $ativo['codigo']
            . ' — '
            . $ativo['descricao'],

        'debito' =>
            $valorAquisicao,

        'credito' =>
            0.00,

        'origem' =>
            'ATIVO',

        'referencia_id' =>
            $ativoId
    ];


    // ==============================================
    // 2. DEPRECIAÇÃO / AMORTIZAÇÃO ACUMULADA
    // ==============================================

    if (
        $depreciacaoAcumulada > 0
    ) {

        $itens[] = [

            'conta_contabil_id' =>
                (int)$ativo[
                    'conta_acumulada_id'
                ],

            'centro_custo_id' =>
                null,

            'descricao' =>
                (
                    strtoupper(
                        (string)$ativo['tipo']
                    ) === 'INTANGIVEL'
                        ? 'Amortização acumulada'
                        : 'Depreciação acumulada'
                )
                . ' — '
                . $ativo['codigo']
                . ' — '
                . $ativo['descricao'],

            'debito' =>
                0.00,

            'credito' =>
                $depreciacaoAcumulada,

            'origem' =>
                'ATIVO',

            'referencia_id' =>
                $ativoId
        ];
    }


    return [

        'ativo_id' =>
            $ativoId,

        'codigo' =>
            $ativo['codigo'],

        'descricao' =>
            $ativo['descricao'],

        'tipo' =>
            $ativo['tipo'],

        'valor_aquisicao' =>
            $valorAquisicao,

        'depreciacao_acumulada' =>
            $depreciacaoAcumulada,

        'valor_liquido' =>
            round(
                $valorAquisicao
                - $depreciacaoAcumulada,
                2
            ),

        'itens' =>
            $itens
    ];
}

    // ==================================================
    // NORMALIZAR ITENS
    // ==================================================

    private function normalizarItens(
        int $empresaId,
        int $planoContasId,
        array $itens
    ): array {

        $resultado = [];

        foreach ($itens as $item) {

            $contaId =
                (int)(
                    $item['conta_contabil_id']
                    ?? 0
                );

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
                $contaId <= 0
            ) {
                continue;
            }

            if (
                $debito <= 0
                && $credito <= 0
            ) {
                continue;
            }

            if (
                $debito > 0
                && $credito > 0
            ) {
                throw new RuntimeException(
                    'Um item não pode possuir débito e crédito simultaneamente.'
                );
            }

            $this->validarConta(
                $empresaId,
                $planoContasId,
                $contaId
            );

            $origem =
                strtoupper(
                    trim(
                        (string)(
                            $item['origem']
                            ?? 'MANUAL'
                        )
                    )
                );

            $origensPermitidas = [
                'MANUAL',
                'STOCK',
                'CAIXA',
                'BANCO',
                'CLIENTE',
                'FORNECEDOR',
                'ATIVO',
                'OUTRO'
            ];

            if (
                !in_array(
                    $origem,
                    $origensPermitidas,
                    true
                )
            ) {
                $origem =
                    'MANUAL';
            }

            $resultado[] = [
                'conta_contabil_id' =>
                $contaId,

                'centro_custo_id' =>
                !empty($item['centro_custo_id'])
                    ? (int)$item['centro_custo_id']
                    : null,

                'descricao' =>
                trim(
                    (string)(
                        $item['descricao']
                        ?? ''
                    )
                )
                    ?: null,

                'debito' =>
                $debito,

                'credito' =>
                $credito,

                'origem' =>
                $origem,

                'referencia_id' =>
                !empty($item['referencia_id'])
                    ? (int)$item['referencia_id']
                    : null
            ];
        }

        if (empty($resultado)) {
            throw new RuntimeException(
                'Adicione pelo menos um item ao saldo de abertura.'
            );
        }

        return $resultado;
    }

    // ==================================================
    // CALCULAR TOTAIS
    // ==================================================

    private function calcularTotais(
        array $itens
    ): array {

        $debito = 0.00;
        $credito = 0.00;

        foreach ($itens as $item) {
            $debito +=
                (float)$item['debito'];

            $credito +=
                (float)$item['credito'];
        }

        return [
            'debito' =>
            round(
                $debito,
                2
            ),

            'credito' =>
            round(
                $credito,
                2
            ),

            'diferenca' =>
            round(
                $debito - $credito,
                2
            )
        ];
    }

    // ==================================================
    // VALIDAR CABEÇALHO
    // ==================================================

    private function validarCabecalho(
        int $empresaId,
        int $exercicioId,
        int $planoContasId,
        ?int $filialId,
        string $data
    ): void {

        if (
            !$this->dataValida(
                $data
            )
        ) {
            throw new RuntimeException(
                'Informe uma data de abertura válida.'
            );
        }

        $this->validarPertenceEmpresa(
            'exercicios_contabilisticos',
            $exercicioId,
            $empresaId,
            'Exercício contabilístico inválido.'
        );

        $this->validarPertenceEmpresa(
            'planos_contas',
            $planoContasId,
            $empresaId,
            'Plano de contas inválido.'
        );

        if ($filialId !== null) {

            $this->validarPertenceEmpresa(
                'filiais',
                $filialId,
                $empresaId,
                'Filial inválida.'
            );
        }
    }

    // ==================================================
    // VALIDAR CONTA
    // ==================================================

    private function validarConta(
        int $empresaId,
        int $planoContasId,
        int $contaId
    ): void {

        $sql = "
            SELECT id

            FROM contas_contabilisticas

            WHERE id = :id
              AND empresa_id = :empresa_id
              AND plano_contas_id =
                    :plano_contas_id

              AND tipo = 'ANALITICA'
              AND aceita_lancamento = 1

            LIMIT 1
        ";

        $stmt =
            $this->pdo->prepare(
                $sql
            );

        $stmt->execute([
            ':id' =>
            $contaId,

            ':empresa_id' =>
            $empresaId,

            ':plano_contas_id' =>
            $planoContasId
        ]);

        if (!$stmt->fetchColumn()) {
            throw new RuntimeException(
                'Uma das contas selecionadas não permite lançamentos.'
            );
        }
    }

    // ==================================================
    // CONTA DE INTEGRAÇÃO
    // ==================================================

    private function contaIntegracao(
        int $empresaId,
        int $planoContasId,
        string $codigo
    ): array {

        $sql = "
            SELECT
                id,
                codigo,
                nome

            FROM contas_contabilisticas

            WHERE empresa_id = :empresa_id

              AND plano_contas_id =
                    :plano_contas_id

              AND integracao_codigo =
                    :integracao_codigo

              AND tipo = 'ANALITICA'
              AND aceita_lancamento = 1

            LIMIT 1
        ";

        $stmt =
            $this->pdo->prepare(
                $sql
            );

        $stmt->execute([
            ':empresa_id' =>
            $empresaId,

            ':plano_contas_id' =>
            $planoContasId,

            ':integracao_codigo' =>
            $codigo
        ]);

        $conta =
            $stmt->fetch(
                PDO::FETCH_ASSOC
            );

        if (!$conta) {
            throw new RuntimeException(
                "Conta de integração {$codigo} não configurada."
            );
        }

        return $conta;
    }

    // ==================================================
    // STATUS DA ABERTURA
    // ==================================================

    private function statusId(
        string $nome
    ): int {

        $stmt =
            $this->pdo->prepare(
                "
                SELECT id

                FROM status

                WHERE tipo = 'saldo_abertura'
                  AND LOWER(nome) =
                        LOWER(:nome)

                LIMIT 1
                "
            );

        $stmt->execute([
            ':nome' =>
            $nome
        ]);

        $id =
            $stmt->fetchColumn();

        if (!$id) {
            throw new RuntimeException(
                "Status '{$nome}' de saldo de abertura não configurado."
            );
        }

        return (int)$id;
    }

    // ==================================================
    // STATUS CONTABILIZADO
    // ==================================================

    private function statusLancamentoContabilizado(): int
    {
        /*
         * Não fixa ID.
         * Procura primeiro pelo tipo utilizado
         * pelos lançamentos contabilísticos.
         */

        $sql = "
            SELECT id

            FROM status

            WHERE LOWER(nome) =
                'contabilizado'

            ORDER BY
                CASE
                    WHEN tipo IN (
                        'lancamento_contabilistico',
                        'lancamento'
                    )
                    THEN 0
                    ELSE 1
                END,
                id

            LIMIT 1
        ";

        $id =
            $this->pdo
            ->query($sql)
            ->fetchColumn();

        if (!$id) {
            throw new RuntimeException(
                'Status Contabilizado não configurado.'
            );
        }

        return (int)$id;
    }

    // ==================================================
    // PERÍODO DA DATA
    // ==================================================

    private function periodoDaData(
        int $empresaId,
        int $exercicioId,
        string $data
    ): array {

        $sql = "
            SELECT
                id,
                numero,
                nome,
                status_id

            FROM periodos_contabilisticos

            WHERE empresa_id = :empresa_id
              AND exercicio_id = :exercicio_id
              AND :data BETWEEN
                    data_inicio
                    AND data_fim

            LIMIT 1
        ";

        $stmt =
            $this->pdo->prepare(
                $sql
            );

        $stmt->execute([
            ':empresa_id' =>
            $empresaId,

            ':exercicio_id' =>
            $exercicioId,

            ':data' =>
            $data
        ]);

        $periodo =
            $stmt->fetch(
                PDO::FETCH_ASSOC
            );

        if (!$periodo) {
            throw new RuntimeException(
                'Não existe período contabilístico para a data de abertura.'
            );
        }

        return $periodo;
    }

    // ==================================================
    // DIÁRIO ABE
    // ==================================================

    private function diarioAbertura(
        int $empresaId
    ): array {

        $sql = "
            SELECT
                id,
                codigo,
                nome

            FROM diarios_contabilisticos

            WHERE empresa_id = :empresa_id
              AND UPPER(codigo) = 'ABE'

            LIMIT 1
        ";

        $stmt =
            $this->pdo->prepare(
                $sql
            );

        $stmt->execute([
            ':empresa_id' =>
            $empresaId
        ]);

        $diario =
            $stmt->fetch(
                PDO::FETCH_ASSOC
            );

        if (!$diario) {
            throw new RuntimeException(
                'Diário ABE — Diário de Abertura não configurado.'
            );
        }

        return $diario;
    }

    public function listarEntradasStockNaoContabilizadas(
        int $empresaId,
        string $dataInicial,
        string $dataFinal,
        ?int $filialId = null
    ): array {

        if (
            !$this->dataValida($dataInicial)
            || !$this->dataValida($dataFinal)
        ) {
            throw new RuntimeException(
                'Período inválido para consultar entradas de stock.'
            );
        }


        $sql = "
        SELECT
            m.id AS movimento_id,
            m.data_movimento,
            m.tipo,
            m.armazem_destino_id,
            m.observacoes,

            a.codigo AS armazem_codigo,
            a.nome AS armazem_nome,

            COUNT(i.id) AS produtos,

            COALESCE(
                SUM(i.quantidade),
                0
            ) AS quantidade_total,

            COALESCE(
                SUM(i.subtotal),
                0
            ) AS valor_total

        FROM movimentos_estoque m

        INNER JOIN movimento_estoque_itens i
            ON i.movimento_id = m.id

        LEFT JOIN armazens a
            ON a.id = m.armazem_destino_id

        WHERE m.empresa_id = :empresa_id

          AND m.tipo = 'ENTRADA'

          AND DATE(m.data_movimento)
                BETWEEN :data_inicial
                    AND :data_final

          AND NOT EXISTS
          (
              SELECT 1

              FROM lancamentos_contabilisticos l

              WHERE l.empresa_id = m.empresa_id

                AND l.origem = 'STOCK'

                AND l.origem_id = m.id

                AND l.status_id =
                (
                    SELECT s.id

                    FROM status s

                    WHERE s.tipo =
                        'lancamento_contabilistico'

                      AND LOWER(s.nome) =
                        'contabilizado'

                    LIMIT 1
                )
          )
    ";


        $params = [
            ':empresa_id' =>
            $empresaId,

            ':data_inicial' =>
            $dataInicial,

            ':data_final' =>
            $dataFinal
        ];


        if ($filialId !== null) {

            $sql .= "
            AND a.filial_id =
                :filial_id
        ";

            $params[':filial_id'] =
                $filialId;
        }


        $sql .= "
        GROUP BY
            m.id,
            m.data_movimento,
            m.tipo,
            m.armazem_destino_id,
            m.observacoes,
            a.codigo,
            a.nome

        ORDER BY
            m.data_movimento,
            m.id
    ";


        $stmt =
            $this->pdo->prepare($sql);

        $stmt->execute($params);


        return $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );
    }

    public function itemEntradaStockAbertura(
        int $empresaId,
        int $planoContasId,
        int $movimentoId
    ): array {

        $sql = "
        SELECT
            m.id,
            m.data_movimento,
            m.observacoes,

            COUNT(i.id) AS produtos,

            COALESCE(
                SUM(i.quantidade),
                0
            ) AS quantidade_total,

            COALESCE(
                SUM(i.subtotal),
                0
            ) AS valor_total

        FROM movimentos_estoque m

        INNER JOIN movimento_estoque_itens i
            ON i.movimento_id = m.id

        WHERE m.id = :movimento_id
          AND m.empresa_id = :empresa_id
          AND m.tipo = 'ENTRADA'

        GROUP BY
            m.id,
            m.data_movimento,
            m.observacoes

        LIMIT 1
    ";


        $stmt =
            $this->pdo->prepare($sql);


        $stmt->execute([
            ':movimento_id' =>
            $movimentoId,

            ':empresa_id' =>
            $empresaId
        ]);


        $movimento =
            $stmt->fetch(
                PDO::FETCH_ASSOC
            );


        if (!$movimento) {

            throw new RuntimeException(
                'Entrada de stock não encontrada.'
            );
        }


        $valor =
            round(
                (float)$movimento['valor_total'],
                2
            );


        if ($valor <= 0) {

            throw new RuntimeException(
                'A entrada de stock não possui valor para abertura.'
            );
        }


        $contaStock =
            $this->contaIntegracao(
                $empresaId,
                $planoContasId,
                'STOCK'
            );


        return [
            'conta_contabil_id' =>
            (int)$contaStock['id'],

            'conta_codigo' =>
            $contaStock['codigo'],

            'conta_nome' =>
            $contaStock['nome'],

            'centro_custo_id' =>
            null,

            'descricao' =>
            'Stock inicial — movimento #'
                . $movimentoId,

            'debito' =>
            $valor,

            'credito' =>
            0.00,

            'origem' =>
            'STOCK',

            'referencia_id' =>
            $movimentoId,

            'detalhes' => [
                'movimento_id' =>
                $movimentoId,

                'data_movimento' =>
                $movimento['data_movimento'],

                'produtos' =>
                (int)$movimento['produtos'],

                'quantidade_total' =>
                (float)$movimento['quantidade_total'],

                'valor_total' =>
                $valor,

                'observacoes' =>
                $movimento['observacoes']
            ]
        ];
    }

    // ==================================================
    // PRÓXIMO NÚMERO
    // ==================================================

    private function proximoNumeroDiario(
        int $empresaId,
        int $diarioId
    ): int {

        $stmt =
            $this->pdo->prepare(
                "
                SELECT
                    COALESCE(
                        MAX(numero),
                        0
                    ) + 1

                FROM lancamentos_contabilisticos

                WHERE empresa_id =
                    :empresa_id

                  AND diario_id =
                    :diario_id
                "
            );

        $stmt->execute([
            ':empresa_id' =>
            $empresaId,

            ':diario_id' =>
            $diarioId
        ]);

        return
            (int)$stmt
                ->fetchColumn();
    }

    // ==================================================
    // VALIDAR REGISTO DA EMPRESA
    // ==================================================

    private function validarPertenceEmpresa(
        string $tabela,
        int $id,
        int $empresaId,
        string $mensagem
    ): void {

        $permitidas = [
            'exercicios_contabilisticos',
            'planos_contas',
            'filiais'
        ];

        if (
            !in_array(
                $tabela,
                $permitidas,
                true
            )
        ) {
            throw new RuntimeException(
                'Tabela de validação inválida.'
            );
        }

        $sql = "
            SELECT id

            FROM {$tabela}

            WHERE id = :id
              AND empresa_id = :empresa_id

            LIMIT 1
        ";

        $stmt =
            $this->pdo->prepare(
                $sql
            );

        $stmt->execute([
            ':id' =>
            $id,

            ':empresa_id' =>
            $empresaId
        ]);

        if (!$stmt->fetchColumn()) {
            throw new RuntimeException(
                $mensagem
            );
        }
    }

    // ==================================================
    // DATA VÁLIDA
    // ==================================================

    private function dataValida(
        string $data
    ): bool {

        $objeto =
            DateTimeImmutable::createFromFormat(
                'Y-m-d',
                $data
            );

        return
            $objeto !== false
            && $objeto->format('Y-m-d')
            === $data;
    }
}
