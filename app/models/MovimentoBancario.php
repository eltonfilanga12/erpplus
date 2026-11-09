<?php

declare(strict_types=1);

final class MovimentoBancario
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    // ==================================================
    // LISTAR MOVIMENTOS
    // ==================================================

    public function listar(
        int $empresaId,
        array $filtros = []
    ): array {

        if ($empresaId <= 0) {
            throw new RuntimeException(
                'Empresa inválida.'
            );
        }

        $sql = "
            SELECT
                m.*,

                b.nome_banco AS banco,
                b.conta AS conta_bancaria,
                b.moeda,

                f.nome AS filial,

                u.nome AS operador

            FROM movimentos_bancarios m

            INNER JOIN bancos b
                ON b.id = m.banco_id

            LEFT JOIN filiais f
                ON f.id = m.filial_id

            LEFT JOIN usuarios u
                ON u.id = m.usuario_id

            WHERE m.empresa_id = :empresa_id
        ";

        $params = [
            ':empresa_id' => $empresaId
        ];

        if (!empty($filtros['banco_id'])) {
            $sql .= "
                AND m.banco_id = :banco_id
            ";

            $params[':banco_id'] =
                (int)$filtros['banco_id'];
        }

        if (!empty($filtros['tipo'])) {
            $sql .= "
                AND m.tipo = :tipo
            ";

            $params[':tipo'] = strtoupper(
                trim((string)$filtros['tipo'])
            );
        }

        if (!empty($filtros['categoria'])) {
            $sql .= "
                AND m.categoria = :categoria
            ";

            $params[':categoria'] = strtoupper(
                trim((string)$filtros['categoria'])
            );
        }

        if (!empty($filtros['data_inicio'])) {
            $sql .= "
                AND DATE(m.data_movimento)
                    >= :data_inicio
            ";

            $params[':data_inicio'] =
                $filtros['data_inicio'];
        }

        if (!empty($filtros['data_fim'])) {
            $sql .= "
                AND DATE(m.data_movimento)
                    <= :data_fim
            ";

            $params[':data_fim'] =
                $filtros['data_fim'];
        }

        if (
            isset($filtros['conciliado'])
            && $filtros['conciliado'] !== ''
        ) {
            $sql .= "
                AND m.conciliado = :conciliado
            ";

            $params[':conciliado'] =
                (int)$filtros['conciliado'];
        }

        $sql .= "
            ORDER BY
                m.data_movimento DESC,
                m.id DESC
        ";

        $stmt = $this->pdo->prepare($sql);

        $stmt->execute($params);

        return $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );
    }

    // ==================================================
    // BUSCAR MOVIMENTO
    // ==================================================

    public function buscar(
        int $id,
        int $empresaId
    ): ?array {

        if ($id <= 0 || $empresaId <= 0) {
            throw new RuntimeException(
                'Movimento inválido.'
            );
        }

        $stmt = $this->pdo->prepare("
            SELECT
                m.*,

                b.nome_banco AS banco,
                b.conta AS conta_bancaria,
                b.moeda,

                f.nome AS filial,

                u.nome AS operador

            FROM movimentos_bancarios m

            INNER JOIN bancos b
                ON b.id = m.banco_id

            LEFT JOIN filiais f
                ON f.id = m.filial_id

            LEFT JOIN usuarios u
                ON u.id = m.usuario_id

            WHERE m.id = :id
              AND m.empresa_id = :empresa_id

            LIMIT 1
        ");

        $stmt->execute([
            ':id' => $id,
            ':empresa_id' => $empresaId
        ]);

        return $stmt->fetch(
            PDO::FETCH_ASSOC
        ) ?: null;
    }

    // ==================================================
    // REGISTRAR MOVIMENTO MANUAL
    // ==================================================

    public function registrar(
        array $dados
    ): int {

        $empresaId = (int)(
            $dados['empresa_id'] ?? 0
        );

        $bancoId = (int)(
            $dados['banco_id'] ?? 0
        );

        $usuarioId = (int)(
            $dados['usuario_id'] ?? 0
        );

        $tipo = strtoupper(
            trim((string)(
                $dados['tipo'] ?? ''
            ))
        );

        $categoria = strtoupper(
            trim((string)(
                $dados['categoria'] ?? ''
            ))
        );

        $valor = (float)(
            $dados['valor'] ?? 0
        );

        $this->validarMovimento(
            $dados,
            $empresaId,
            $bancoId,
            $usuarioId,
            $tipo,
            $categoria,
            $valor
        );

        /*
         * A rotina também é utilizada por integrações que já possuem
         * uma transação aberta (por exemplo, pagamento salarial).
         */
        $transacaoPropria = !$this->pdo->inTransaction();

        if ($transacaoPropria) {
            $this->pdo->beginTransaction();
        }

        try {
            $movimentoId =
                $this->aplicarMovimento([
                    'empresa_id' => $empresaId,

                    'filial_id' =>
                    $dados['filial_id']
                        ?? null,

                    'centro_custo_id' =>
                    $dados['centro_custo_id']
                        ?? null,

                    'banco_id' => $bancoId,

                    'usuario_id' => $usuarioId,

                    'tipo' => $tipo,

                    'categoria' => $categoria,

                    'origem' =>
                    $dados['origem']
                        ?? 'MANUAL',

                    'origem_id' =>
                    $dados['origem_id']
                        ?? null,

                    'descricao' =>
                    $dados['descricao']
                        ?? null,

                    'documento' =>
                    $dados['documento']
                        ?? null,

                    'referencia' =>
                    $dados['referencia']
                        ?? null,

                    'valor' => $valor,

                    'data_movimento' =>
                    $dados['data_movimento']
                        ?? null,

                    'observacoes' =>
                    $dados['observacoes']
                        ?? null
                ]);

            if ($transacaoPropria) {
                $this->pdo->commit();
            }

            return $movimentoId;
        } catch (Throwable $e) {
            if (
                $transacaoPropria
                && $this->pdo->inTransaction()
            ) {
                $this->pdo->rollBack();
            }

            throw $e;
        }
    }

    // ==================================================
    // TRANSFERÊNCIA BANCÁRIA
    // ==================================================

    public function transferir(
        array $dados
    ): array {

        $empresaId = (int)(
            $dados['empresa_id'] ?? 0
        );

        $usuarioId = (int)(
            $dados['usuario_id'] ?? 0
        );

        $bancoOrigemId = (int)(
            $dados['banco_origem_id'] ?? 0
        );

        $bancoDestinoId = (int)(
            $dados['banco_destino_id'] ?? 0
        );

        $valor = (float)(
            $dados['valor'] ?? 0
        );

        $taxa = (float)(
            $dados['taxa'] ?? 0
        );

        if ($empresaId <= 0) {
            throw new RuntimeException(
                'Empresa inválida.'
            );
        }

        if ($usuarioId <= 0) {
            throw new RuntimeException(
                'Utilizador inválido.'
            );
        }

        if (
            $bancoOrigemId <= 0
            || $bancoDestinoId <= 0
        ) {
            throw new RuntimeException(
                'Selecione os bancos de origem e destino.'
            );
        }

        if (
            $bancoOrigemId ===
            $bancoDestinoId
        ) {
            throw new RuntimeException(
                'O banco de origem e destino devem ser diferentes.'
            );
        }

        if ($valor <= 0) {
            throw new RuntimeException(
                'O valor da transferência deve ser maior que zero.'
            );
        }

        if ($taxa < 0) {
            throw new RuntimeException(
                'A taxa não pode ser negativa.'
            );
        }

        $dataTransferencia =
            $this->normalizarData(
                $dados['data_transferencia']
                    ?? null
            );

        $descricao =
            $this->textoOuNull(
                $dados['descricao']
                    ?? null
            )
            ?? 'Transferência bancária';

        $this->pdo->beginTransaction();

        try {
            /*
             * Bloqueia as duas contas sempre na mesma ordem,
             * reduzindo o risco de deadlock.
             */

            $ids = [
                $bancoOrigemId,
                $bancoDestinoId
            ];

            sort($ids);

            foreach ($ids as $idBanco) {
                $this->buscarBancoBloqueado(
                    $idBanco,
                    $empresaId
                );
            }

            $bancoOrigem =
                $this->buscarBancoBloqueado(
                    $bancoOrigemId,
                    $empresaId
                );

            $bancoDestino =
                $this->buscarBancoBloqueado(
                    $bancoDestinoId,
                    $empresaId
                );

            $totalDebito = $valor + $taxa;

            if (
                (float)$bancoOrigem['saldo_atual']
                < $totalDebito
            ) {
                throw new RuntimeException(
                    'Saldo insuficiente no banco de origem.'
                );
            }

            /*
             * Primeiro cria o registo geral da transferência.
             */

            $stmt = $this->pdo->prepare("
                INSERT INTO transferencias_bancarias
                (
                    empresa_id,
                    filial_id,
                    banco_origem_id,
                    banco_destino_id,
                    usuario_id,
                    valor,
                    taxa,
                    descricao,
                    documento,
                    referencia,
                    data_transferencia,
                    observacoes
                )
                VALUES
                (
                    :empresa_id,
                    :filial_id,
                    :banco_origem_id,
                    :banco_destino_id,
                    :usuario_id,
                    :valor,
                    :taxa,
                    :descricao,
                    :documento,
                    :referencia,
                    :data_transferencia,
                    :observacoes
                )
            ");

            $stmt->execute([
                ':empresa_id' => $empresaId,

                ':filial_id' =>
                !empty($dados['filial_id'])
                    ? (int)$dados['filial_id']
                    : null,

                ':banco_origem_id' =>
                $bancoOrigemId,

                ':banco_destino_id' =>
                $bancoDestinoId,

                ':usuario_id' =>
                $usuarioId,

                ':valor' =>
                $valor,

                ':taxa' =>
                $taxa,

                ':descricao' =>
                $descricao,

                ':documento' =>
                $this->textoOuNull(
                    $dados['documento']
                        ?? null
                ),

                ':referencia' =>
                $this->textoOuNull(
                    $dados['referencia']
                        ?? null
                ),

                ':data_transferencia' =>
                $dataTransferencia,

                ':observacoes' =>
                $this->textoOuNull(
                    $dados['observacoes']
                        ?? null
                )
            ]);

            $transferenciaId =
                (int)$this->pdo
                    ->lastInsertId();

            /*
             * Saída no banco de origem.
             */

            $movimentoSaidaId =
                $this->aplicarMovimento([
                    'empresa_id' =>
                    $empresaId,

                    'filial_id' =>
                    $dados['filial_id']
                        ?? null,

                    'banco_id' =>
                    $bancoOrigemId,

                    'usuario_id' =>
                    $usuarioId,

                    'tipo' =>
                    'SAIDA',

                    'categoria' =>
                    'TRANSFERENCIA',

                    'origem' =>
                    'TRANSFERENCIA_BANCARIA',

                    'origem_id' =>
                    $transferenciaId,

                    'descricao' =>
                    $descricao
                        . ' para '
                        . $bancoDestino['nome_banco'],

                    'documento' =>
                    $dados['documento']
                        ?? null,

                    'referencia' =>
                    $dados['referencia']
                        ?? null,

                    'valor' =>
                    $valor,

                    'data_movimento' =>
                    $dataTransferencia,

                    'observacoes' =>
                    $dados['observacoes']
                        ?? null
                ]);

            /*
             * Entrada no banco de destino.
             */

            $movimentoEntradaId =
                $this->aplicarMovimento([
                    'empresa_id' =>
                    $empresaId,

                    'filial_id' =>
                    $dados['filial_id']
                        ?? null,

                    'banco_id' =>
                    $bancoDestinoId,

                    'usuario_id' =>
                    $usuarioId,

                    'tipo' =>
                    'ENTRADA',

                    'categoria' =>
                    'TRANSFERENCIA',

                    'origem' =>
                    'TRANSFERENCIA_BANCARIA',

                    'origem_id' =>
                    $transferenciaId,

                    'descricao' =>
                    $descricao
                        . ' recebida de '
                        . $bancoOrigem['nome_banco'],

                    'documento' =>
                    $dados['documento']
                        ?? null,

                    'referencia' =>
                    $dados['referencia']
                        ?? null,

                    'valor' =>
                    $valor,

                    'data_movimento' =>
                    $dataTransferencia,

                    'observacoes' =>
                    $dados['observacoes']
                        ?? null
                ]);

            /*
             * Se houver taxa, cria uma saída separada.
             */

            $movimentoTaxaId = null;

            if ($taxa > 0) {
                $movimentoTaxaId =
                    $this->aplicarMovimento([
                        'empresa_id' =>
                        $empresaId,

                        'filial_id' =>
                        $dados['filial_id']
                            ?? null,

                        'banco_id' =>
                        $bancoOrigemId,

                        'usuario_id' =>
                        $usuarioId,

                        'tipo' =>
                        'SAIDA',

                        'categoria' =>
                        'TARIFA',

                        'origem' =>
                        'TRANSFERENCIA_BANCARIA',

                        'origem_id' =>
                        $transferenciaId,

                        'descricao' =>
                        'Taxa da transferência bancária',

                        'documento' =>
                        $dados['documento']
                            ?? null,

                        'referencia' =>
                        $dados['referencia']
                            ?? null,

                        'valor' =>
                        $taxa,

                        'data_movimento' =>
                        $dataTransferencia,

                        'observacoes' =>
                        $dados['observacoes']
                            ?? null
                    ]);
            }

            /*
             * Relaciona os movimentos à transferência.
             */

            $stmt = $this->pdo->prepare("
                UPDATE transferencias_bancarias
                SET
                    movimento_saida_id =
                        :movimento_saida_id,

                    movimento_entrada_id =
                        :movimento_entrada_id

                WHERE id = :id
                  AND empresa_id = :empresa_id
            ");

            $stmt->execute([
                ':movimento_saida_id' =>
                $movimentoSaidaId,

                ':movimento_entrada_id' =>
                $movimentoEntradaId,

                ':id' =>
                $transferenciaId,

                ':empresa_id' =>
                $empresaId
            ]);

            $this->pdo->commit();

            return [
                'transferencia_id' =>
                $transferenciaId,

                'movimento_saida_id' =>
                $movimentoSaidaId,

                'movimento_entrada_id' =>
                $movimentoEntradaId,

                'movimento_taxa_id' =>
                $movimentoTaxaId,

                'valor' =>
                $valor,

                'taxa' =>
                $taxa,

                'total_debitado' =>
                $totalDebito
            ];
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw $e;
        }
    }

    // ==================================================
    // EXTRATO
    // ==================================================

    public function extrato(
        int $bancoId,
        int $empresaId,
        array $filtros = []
    ): array {

        $filtros['banco_id'] = $bancoId;

        return $this->listar(
            $empresaId,
            $filtros
        );
    }

    // ==================================================
    // SALDO ATUAL
    // ==================================================

    public function saldoAtual(
        int $bancoId,
        int $empresaId
    ): float {

        $stmt = $this->pdo->prepare("
            SELECT saldo_atual
            FROM bancos
            WHERE id = :banco_id
              AND empresa_id = :empresa_id
            LIMIT 1
        ");

        $stmt->execute([
            ':banco_id' => $bancoId,
            ':empresa_id' => $empresaId
        ]);

        $saldo = $stmt->fetchColumn();

        if ($saldo === false) {
            throw new RuntimeException(
                'Banco não encontrado.'
            );
        }

        return (float)$saldo;
    }

    // ==================================================
    // APLICAR MOVIMENTO
    // NÃO ABRE TRANSAÇÃO
    // ==================================================

    private function aplicarMovimento(
        array $dados
    ): int {

        $empresaId = (int)(
            $dados['empresa_id'] ?? 0
        );

        $bancoId = (int)(
            $dados['banco_id'] ?? 0
        );

        $usuarioId = (int)(
            $dados['usuario_id'] ?? 0
        );

        $tipo = strtoupper(
            trim((string)(
                $dados['tipo'] ?? ''
            ))
        );

        $categoria = strtoupper(
            trim((string)(
                $dados['categoria'] ?? ''
            ))
        );

        $valor = (float)(
            $dados['valor'] ?? 0
        );

        $this->validarMovimento(
            $dados,
            $empresaId,
            $bancoId,
            $usuarioId,
            $tipo,
            $categoria,
            $valor
        );

        $banco =
            $this->buscarBancoBloqueado(
                $bancoId,
                $empresaId
            );

        $saldoAnterior =
            (float)$banco['saldo_atual'];

        $entrada =
            $tipo === 'ENTRADA';

        if (!$entrada && $saldoAnterior < $valor) {
            throw new RuntimeException(
                'Saldo insuficiente na conta bancária.'
            );
        }

        $saldoPosterior =
            $entrada
            ? $saldoAnterior + $valor
            : $saldoAnterior - $valor;

        $dataMovimento =
            $this->normalizarData(
                $dados['data_movimento']
                    ?? null
            );

        $centroCustoId =
            $this->resolverCentroCustoId(
                $empresaId,
                !empty($dados['centro_custo_id'])
                    ? (int)$dados['centro_custo_id']
                    : null
            );

        $stmt = $this->pdo->prepare("
            INSERT INTO movimentos_bancarios
            (
                empresa_id,
                filial_id,
                centro_custo_id,
                banco_id,
                usuario_id,
                tipo,
                categoria,
                origem,
                origem_id,
                descricao,
                documento,
                referencia,
                valor,
                saldo_anterior,
                saldo_posterior,
                data_movimento,
                observacoes
            )
            VALUES
            (
                :empresa_id,
                :filial_id,
                :centro_custo_id,
                :banco_id,
                :usuario_id,
                :tipo,
                :categoria,
                :origem,
                :origem_id,
                :descricao,
                :documento,
                :referencia,
                :valor,
                :saldo_anterior,
                :saldo_posterior,
                :data_movimento,
                :observacoes
            )
        ");

        $stmt->execute([
            ':empresa_id' => $empresaId,

            ':filial_id' =>
            !empty($dados['filial_id'])
                ? (int)$dados['filial_id']
                : (
                    !empty($banco['filial_id'])
                    ? (int)$banco['filial_id']
                    : null
                ),

            ':centro_custo_id' =>
            $centroCustoId,

            ':banco_id' => $bancoId,

            ':usuario_id' => $usuarioId,

            ':tipo' => $tipo,

            ':categoria' => $categoria,

            ':origem' =>
            trim((string)(
                $dados['origem']
                ?? 'MANUAL'
            )),

            ':origem_id' =>
            !empty($dados['origem_id'])
                ? (int)$dados['origem_id']
                : null,

            ':descricao' =>
            trim((string)$dados['descricao']),

            ':documento' =>
            $this->textoOuNull(
                $dados['documento']
                    ?? null
            ),

            ':referencia' =>
            $this->textoOuNull(
                $dados['referencia']
                    ?? null
            ),

            ':valor' => $valor,

            ':saldo_anterior' =>
            $saldoAnterior,

            ':saldo_posterior' =>
            $saldoPosterior,

            ':data_movimento' =>
            $dataMovimento,

            ':observacoes' =>
            $this->textoOuNull(
                $dados['observacoes']
                    ?? null
            )
        ]);

        $movimentoId =
            (int)$this->pdo
                ->lastInsertId();

        $stmt = $this->pdo->prepare("
            UPDATE bancos
            SET saldo_atual = :saldo_atual
            WHERE id = :banco_id
              AND empresa_id = :empresa_id
        ");

        $stmt->execute([
            ':saldo_atual' =>
            $saldoPosterior,

            ':banco_id' =>
            $bancoId,

            ':empresa_id' =>
            $empresaId
        ]);

        if ($stmt->rowCount() !== 1) {
            throw new RuntimeException(
                'Não foi possível atualizar o saldo bancário.'
            );
        }

        return $movimentoId;
    }

    // ==================================================
    // CENTRO DE CUSTO DO MOVIMENTO
    // ==================================================

    private function resolverCentroCustoId(
        int $empresaId,
        ?int $centroCustoId
    ): int {

        if ($centroCustoId !== null && $centroCustoId > 0) {
            $stmt = $this->pdo->prepare("
                SELECT id
                FROM centros_custo
                WHERE id = :centro_custo_id
                  AND empresa_id = :empresa_id
                LIMIT 1
            ");

            $stmt->execute([
                ':centro_custo_id' => $centroCustoId,
                ':empresa_id' => $empresaId
            ]);

            $id = $stmt->fetchColumn();

            if ($id === false) {
                throw new RuntimeException(
                    'O centro de custo informado não pertence à empresa.'
                );
            }

            return (int)$id;
        }

        $stmt = $this->pdo->prepare("
            SELECT id
            FROM centros_custo
            WHERE empresa_id = :empresa_id
            ORDER BY id ASC
            LIMIT 2
        ");

        $stmt->execute([
            ':empresa_id' => $empresaId
        ]);

        $centros = $stmt->fetchAll(
            PDO::FETCH_COLUMN
        );

        if (count($centros) === 0) {
            throw new RuntimeException(
                'Não existe centro de custo configurado para a empresa.'
            );
        }

        if (count($centros) > 1) {
            throw new RuntimeException(
                'Selecione o centro de custo do movimento.'
            );
        }

        return (int)$centros[0];
    }

    // ==================================================
    // BUSCAR E BLOQUEAR BANCO
    // ==================================================

    private function buscarBancoBloqueado(
        int $bancoId,
        int $empresaId
    ): array {

        $stmt = $this->pdo->prepare("
            SELECT
                b.id,
                b.empresa_id,
                b.filial_id,
                b.status_id,
                b.nome_banco,
                b.conta,
                b.moeda,
                b.saldo_atual,
                s.nome AS status

            FROM bancos b

            INNER JOIN status s
                ON s.id = b.status_id

            WHERE b.id = :banco_id
              AND b.empresa_id = :empresa_id

            LIMIT 1

            FOR UPDATE
        ");

        $stmt->execute([
            ':banco_id' => $bancoId,
            ':empresa_id' => $empresaId
        ]);

        $banco = $stmt->fetch(
            PDO::FETCH_ASSOC
        );

        if (!$banco) {
            throw new RuntimeException(
                'Conta bancária não encontrada.'
            );
        }

        if (
            strtolower(
                trim((string)$banco['status'])
            ) !== 'activo'
        ) {
            throw new RuntimeException(
                'A conta bancária "'
                    . $banco['nome_banco']
                    . '" não está activa.'
            );
        }

        return $banco;
    }

    // ==================================================
    // VALIDAR MOVIMENTO
    // ==================================================

    private function validarMovimento(
        array $dados,
        int $empresaId,
        int $bancoId,
        int $usuarioId,
        string $tipo,
        string $categoria,
        float $valor
    ): void {

        if ($empresaId <= 0) {
            throw new RuntimeException(
                'Empresa inválida.'
            );
        }

        if ($bancoId <= 0) {
            throw new RuntimeException(
                'Selecione uma conta bancária.'
            );
        }

        if ($usuarioId <= 0) {
            throw new RuntimeException(
                'Utilizador inválido.'
            );
        }

        if (
            !in_array(
                $tipo,
                ['ENTRADA', 'SAIDA'],
                true
            )
        ) {
            throw new RuntimeException(
                'Tipo de movimento inválido.'
            );
        }

        $categoriasPermitidas = [
            'DEPOSITO',
            'LEVANTAMENTO',
            'TRANSFERENCIA',
            'PAGAMENTO',
            'RECEBIMENTO',
            'TARIFA',
            'JURO',
            'AJUSTE',
            'OUTRO'
        ];

        if (
            !in_array(
                $categoria,
                $categoriasPermitidas,
                true
            )
        ) {
            throw new RuntimeException(
                'Categoria bancária inválida.'
            );
        }

        if ($valor <= 0) {
            throw new RuntimeException(
                'O valor deve ser maior que zero.'
            );
        }

        if (
            empty($dados['descricao'])
            || trim(
                (string)$dados['descricao']
            ) === ''
        ) {
            throw new RuntimeException(
                'Informe a descrição do movimento.'
            );
        }
    }

    // ==================================================
    // NORMALIZAR DATA
    // ==================================================

    private function normalizarData(
        mixed $data
    ): string {

        if (
            $data === null
            || trim((string)$data) === ''
        ) {
            return date('Y-m-d H:i:s');
        }

        $valor = trim((string)$data);

        /*
         * datetime-local envia:
         * 2026-07-25T14:30
         */

        $valor = str_replace(
            'T',
            ' ',
            $valor
        );

        $timestamp = strtotime($valor);

        if ($timestamp === false) {
            throw new RuntimeException(
                'Data do movimento inválida.'
            );
        }

        return date(
            'Y-m-d H:i:s',
            $timestamp
        );
    }

    // ==================================================
    // TEXTO OU NULL
    // ==================================================

    private function textoOuNull(
        mixed $valor
    ): ?string {

        if ($valor === null) {
            return null;
        }

        $texto = trim(
            (string)$valor
        );

        return $texto === ''
            ? null
            : $texto;
    }
}
