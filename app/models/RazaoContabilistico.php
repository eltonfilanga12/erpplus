<?php

declare(strict_types=1);

final class RazaoContabilistico
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    // ==================================================
    // GERAR RAZÃO
    // ==================================================

    public function gerar(
        int $empresaId,
        int $contaContabilId,
        string $dataInicio,
        string $dataFim,
        ?int $filialId = null
    ): array {

        $this->validarEmpresa(
            $empresaId
        );

        $this->validarConta(
            $contaContabilId,
            $empresaId
        );

        $this->validarData(
            $dataInicio,
            'Data inicial'
        );

        $this->validarData(
            $dataFim,
            'Data final'
        );

        if ($dataFim < $dataInicio) {
            throw new RuntimeException(
                'A data final não pode ser anterior à data inicial.'
            );
        }

        if ($filialId !== null) {
            $this->validarFilial(
                $filialId,
                $empresaId
            );
        }

        $statusContabilizado =
            $this->statusLancamento(
                'Contabilizado'
            );

        $conta =
            $this->buscarConta(
                $contaContabilId,
                $empresaId
            );

        $saldoInicial =
            $this->calcularSaldoInicial(
                $empresaId,
                $contaContabilId,
                $dataInicio,
                $statusContabilizado,
                $filialId
            );

        $movimentos =
            $this->listarMovimentos(
                $empresaId,
                $contaContabilId,
                $dataInicio,
                $dataFim,
                $statusContabilizado,
                $filialId
            );

        return $this->calcularSaldos(
            $conta,
            $saldoInicial,
            $movimentos,
            $dataInicio,
            $dataFim,
            $filialId
        );
    }

    // ==================================================
    // SALDO INICIAL
    // ==================================================

    private function calcularSaldoInicial(
        int $empresaId,
        int $contaContabilId,
        string $dataInicio,
        int $statusContabilizado,
        ?int $filialId
    ): float {

        $sql = "
            SELECT
                COALESCE(
                    SUM(
                        item.debito
                        - item.credito
                    ),
                    0
                )

            FROM lancamento_contabilistico_itens item

            INNER JOIN lancamentos_contabilisticos lancamento
                ON lancamento.id =
                    item.lancamento_id

            WHERE lancamento.empresa_id =
                :empresa_id

              AND item.conta_contabil_id =
                :conta_contabil_id

              AND lancamento.status_id =
                :status_contabilizado

              AND lancamento.data_lancamento
                    < :data_inicio
        ";

        $params = [
            ':empresa_id' =>
            $empresaId,

            ':conta_contabil_id' =>
            $contaContabilId,

            ':status_contabilizado' =>
            $statusContabilizado,

            ':data_inicio' =>
            $dataInicio
        ];

        if ($filialId !== null) {
            $sql .= "
                AND lancamento.filial_id =
                    :filial_id
            ";

            $params[':filial_id'] =
                $filialId;
        }

        $stmt =
            $this->pdo->prepare(
                $sql
            );

        $stmt->execute(
            $params
        );

        return round(
            (float)$stmt->fetchColumn(),
            2
        );
    }

    // ==================================================
    // LISTAR MOVIMENTOS
    // ==================================================

    private function listarMovimentos(
        int $empresaId,
        int $contaContabilId,
        string $dataInicio,
        string $dataFim,
        int $statusContabilizado,
        ?int $filialId
    ): array {

        $sql = "
            SELECT
                lancamento.id AS lancamento_id,
                lancamento.numero,
                lancamento.numero_documento,
                lancamento.referencia,
                lancamento.data_lancamento,
                lancamento.data_documento,
                lancamento.descricao
                    AS lancamento_descricao,
                lancamento.origem,
                lancamento.origem_id,

                diario.codigo
                    AS diario_codigo,

                diario.nome
                    AS diario_nome,

                item.id
                    AS item_id,

                item.descricao
                    AS item_descricao,

                item.debito,
                item.credito,

                cliente.nome
                    AS cliente_nome,

                fornecedor.nome
                    AS fornecedor_nome,

                centro.codigo
                    AS centro_custo_codigo,

                centro.nome
                    AS centro_custo_nome

            FROM lancamento_contabilistico_itens item

            INNER JOIN lancamentos_contabilisticos lancamento
                ON lancamento.id =
                    item.lancamento_id

            INNER JOIN diarios_contabilisticos diario
                ON diario.id =
                    lancamento.diario_id

            LEFT JOIN clientes cliente
                ON cliente.id =
                    item.cliente_id

            LEFT JOIN fornecedores fornecedor
                ON fornecedor.id =
                    item.fornecedor_id

            LEFT JOIN centros_custo centro
                ON centro.id =
                    item.centro_custo_id

            WHERE lancamento.empresa_id =
                :empresa_id

              AND item.conta_contabil_id =
                :conta_contabil_id

              AND lancamento.status_id =
                :status_contabilizado

              AND lancamento.data_lancamento
                    BETWEEN
                        :data_inicio
                        AND
                        :data_fim
        ";

        $params = [
            ':empresa_id' =>
            $empresaId,

            ':conta_contabil_id' =>
            $contaContabilId,

            ':status_contabilizado' =>
            $statusContabilizado,

            ':data_inicio' =>
            $dataInicio,

            ':data_fim' =>
            $dataFim
        ];

        if ($filialId !== null) {
            $sql .= "
                AND lancamento.filial_id =
                    :filial_id
            ";

            $params[':filial_id'] =
                $filialId;
        }

        $sql .= "
            ORDER BY
                lancamento.data_lancamento ASC,
                lancamento.numero ASC,
                item.ordem ASC,
                item.id ASC
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
    // CALCULAR SALDOS
    // ==================================================

    private function calcularSaldos(
        array $conta,
        float $saldoInicialLiquido,
        array $movimentos,
        string $dataInicio,
        string $dataFim,
        ?int $filialId
    ): array {

        $saldoCorrente =
            round(
                $saldoInicialLiquido,
                2
            );

        $totalDebitos =
            0.0;

        $totalCreditos =
            0.0;

        $linhas = [];

        foreach ($movimentos as $movimento) {

            $debito =
                round(
                    (float)$movimento['debito'],
                    2
                );

            $credito =
                round(
                    (float)$movimento['credito'],
                    2
                );

            $saldoCorrente =
                round(
                    $saldoCorrente
                        + $debito
                        - $credito,
                    2
                );

            $totalDebitos +=
                $debito;

            $totalCreditos +=
                $credito;

            $linhas[] = [
                'lancamento_id' =>
                (int)$movimento['lancamento_id'],

                'item_id' =>
                (int)$movimento['item_id'],

                'data_lancamento' =>
                $movimento['data_lancamento'],

                'data_documento' =>
                $movimento['data_documento'],

                'numero' =>
                (int)$movimento['numero'],

                'numero_documento' =>
                $movimento['numero_documento'],

                'referencia' =>
                $movimento['referencia'],

                'diario_codigo' =>
                $movimento['diario_codigo'],

                'diario_nome' =>
                $movimento['diario_nome'],

                'descricao' =>
                $movimento['item_descricao']
                    ?: $movimento['lancamento_descricao'],

                'origem' =>
                $movimento['origem'],

                'origem_id' =>
                $movimento['origem_id'] !== null
                    ? (int)$movimento['origem_id']
                    : null,

                'cliente_nome' =>
                $movimento['cliente_nome'],

                'fornecedor_nome' =>
                $movimento['fornecedor_nome'],

                'centro_custo_codigo' =>
                $movimento['centro_custo_codigo'],

                'centro_custo_nome' =>
                $movimento['centro_custo_nome'],

                'debito' =>
                $debito,

                'credito' =>
                $credito,

                'saldo_devedor' =>
                $saldoCorrente > 0
                    ? $saldoCorrente
                    : 0.0,

                'saldo_credor' =>
                $saldoCorrente < 0
                    ? abs(
                        $saldoCorrente
                    )
                    : 0.0
            ];
        }

        $saldoInicialDevedor =
            $saldoInicialLiquido > 0
            ? $saldoInicialLiquido
            : 0.0;

        $saldoInicialCredor =
            $saldoInicialLiquido < 0
            ? abs(
                $saldoInicialLiquido
            )
            : 0.0;

        $saldoFinalDevedor =
            $saldoCorrente > 0
            ? $saldoCorrente
            : 0.0;

        $saldoFinalCredor =
            $saldoCorrente < 0
            ? abs(
                $saldoCorrente
            )
            : 0.0;

        return [
            'filtros' => [
                'data_inicio' =>
                $dataInicio,

                'data_fim' =>
                $dataFim,

                'filial_id' =>
                $filialId
            ],

            'conta' => [
                'id' =>
                (int)$conta['id'],

                'codigo' =>
                (string)$conta['codigo'],

                'nome' =>
                (string)$conta['nome'],

                'natureza' =>
                (string)$conta['natureza'],

                'grupo' =>
                (string)$conta['grupo']
            ],

            'saldo_inicial_devedor' =>
            round(
                $saldoInicialDevedor,
                2
            ),

            'saldo_inicial_credor' =>
            round(
                $saldoInicialCredor,
                2
            ),

            'total_debitos' =>
            round(
                $totalDebitos,
                2
            ),

            'total_creditos' =>
            round(
                $totalCreditos,
                2
            ),

            'saldo_final_devedor' =>
            round(
                $saldoFinalDevedor,
                2
            ),

            'saldo_final_credor' =>
            round(
                $saldoFinalCredor,
                2
            ),

            'linhas' =>
            $linhas
        ];
    }

    // ==================================================
    // CONTAS PARA SELECT
    // ==================================================

    public function listarContas(
        int $empresaId,
        ?int $planoContasId = null
    ): array {

        $this->validarEmpresa(
            $empresaId
        );

        $sql = "
            SELECT
                conta.id,
                conta.codigo,
                conta.nome,
                conta.grupo,
                conta.natureza,
                conta.plano_contas_id

            FROM contas_contabilisticas conta

            INNER JOIN status
                ON status.id =
                    conta.status_id

               AND status.tipo =
                    'conta_contabilistica'

            WHERE conta.empresa_id =
                :empresa_id

              AND conta.tipo =
                    'ANALITICA'

              AND conta.aceita_lancamento =
                    1

              AND LOWER(status.nome)
                    IN
                    (
                        'activo',
                        'ativo',
                        'activa',
                        'ativa'
                    )
        ";

        $params = [
            ':empresa_id' =>
            $empresaId
        ];

        if ($planoContasId !== null) {

            if ($planoContasId <= 0) {
                throw new RuntimeException(
                    'Plano de contas inválido.'
                );
            }

            $sql .= "
                AND conta.plano_contas_id =
                    :plano_contas_id
            ";

            $params[':plano_contas_id'] =
                $planoContasId;
        }

        $sql .= "
            ORDER BY
                conta.codigo ASC
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
    // PLANOS
    // ==================================================

    public function listarPlanos(
        int $empresaId
    ): array {

        $this->validarEmpresa(
            $empresaId
        );

        $stmt =
            $this->pdo->prepare("
                SELECT
                    plano.id,
                    plano.codigo,
                    plano.nome,
                    plano.padrao

                FROM planos_contas plano

                INNER JOIN status
                    ON status.id =
                        plano.status_id

                   AND status.tipo =
                        'plano_contas'

                WHERE plano.empresa_id =
                    :empresa_id

                  AND LOWER(status.nome)
                        IN
                        (
                            'activo',
                            'ativo',
                            'activa',
                            'ativa'
                        )

                ORDER BY
                    plano.padrao DESC,
                    plano.codigo ASC
            ");

        $stmt->execute([
            ':empresa_id' =>
            $empresaId
        ]);

        return $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );
    }

    // ==================================================
    // FILIAIS
    // ==================================================

    public function listarFiliais(
        int $empresaId
    ): array {

        $this->validarEmpresa(
            $empresaId
        );

        $stmt =
            $this->pdo->prepare("
                SELECT
                    id,
                    codigo,
                    nome

                FROM filiais

                WHERE empresa_id =
                    :empresa_id

                ORDER BY
                    nome ASC
            ");

        $stmt->execute([
            ':empresa_id' =>
            $empresaId
        ]);

        return $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );
    }

    // ==================================================
    // BUSCAR CONTA
    // ==================================================

    private function buscarConta(
        int $contaContabilId,
        int $empresaId
    ): array {

        $stmt =
            $this->pdo->prepare("
                SELECT
                    id,
                    codigo,
                    nome,
                    natureza,
                    grupo,
                    tipo,
                    aceita_lancamento

                FROM contas_contabilisticas

                WHERE id =
                    :id

                  AND empresa_id =
                    :empresa_id

                LIMIT 1
            ");

        $stmt->execute([
            ':id' =>
            $contaContabilId,

            ':empresa_id' =>
            $empresaId
        ]);

        $conta =
            $stmt->fetch(
                PDO::FETCH_ASSOC
            );

        if (!$conta) {
            throw new RuntimeException(
                'Conta contabilística não encontrada.'
            );
        }

        if (
            $conta['tipo'] !== 'ANALITICA'
            || (int)$conta['aceita_lancamento'] !== 1
        ) {
            throw new RuntimeException(
                'A conta selecionada não aceita lançamentos.'
            );
        }

        return $conta;
    }

    private function validarConta(
        int $contaContabilId,
        int $empresaId
    ): void {

        if ($contaContabilId <= 0) {
            throw new RuntimeException(
                'Selecione a conta contabilística.'
            );
        }

        $this->buscarConta(
            $contaContabilId,
            $empresaId
        );
    }

    // ==================================================
    // VALIDAR FILIAL
    // ==================================================

    private function validarFilial(
        int $filialId,
        int $empresaId
    ): void {

        $stmt =
            $this->pdo->prepare("
                SELECT id

                FROM filiais

                WHERE id =
                    :id

                  AND empresa_id =
                    :empresa_id

                LIMIT 1
            ");

        $stmt->execute([
            ':id' =>
            $filialId,

            ':empresa_id' =>
            $empresaId
        ]);

        if (!$stmt->fetchColumn()) {
            throw new RuntimeException(
                'A filial selecionada não pertence à empresa.'
            );
        }
    }

    // ==================================================
    // STATUS DO LANÇAMENTO
    // ==================================================

    private function statusLancamento(
        string $nome
    ): int {

        $stmt =
            $this->pdo->prepare("
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
            $nome
        ]);

        $id =
            (int)$stmt->fetchColumn();

        if ($id <= 0) {
            throw new RuntimeException(
                "Status '{$nome}' do lançamento contabilístico não encontrado."
            );
        }

        return $id;
    }

    // ==================================================
    // VALIDAÇÕES
    // ==================================================

    private function validarEmpresa(
        int $empresaId
    ): void {

        if ($empresaId <= 0) {
            throw new RuntimeException(
                'Empresa inválida para gerar o Razão.'
            );
        }
    }

    private function validarData(
        string $data,
        string $campo
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
                $campo . ' inválida.'
            );
        }
    }
}
