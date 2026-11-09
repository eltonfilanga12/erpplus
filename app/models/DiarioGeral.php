<?php

declare(strict_types=1);

final class DiarioGeral
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    // ==================================================
    // GERAR DIÁRIO GERAL
    // ==================================================

    public function gerar(
        int $empresaId,
        string $dataInicio,
        string $dataFim,
        ?int $filialId = null,
        ?int $diarioId = null
    ): array {

        $this->validarEmpresa(
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

        if ($diarioId !== null) {
            $this->validarDiario(
                $diarioId,
                $empresaId
            );
        }

        $statusContabilizado =
            $this->statusLancamento(
                'Contabilizado'
            );

        $lancamentos =
            $this->listarLancamentos(
                $empresaId,
                $dataInicio,
                $dataFim,
                $statusContabilizado,
                $filialId,
                $diarioId
            );

        return $this->montarResultado(
            $lancamentos,
            $dataInicio,
            $dataFim,
            $filialId,
            $diarioId
        );
    }

    // ==================================================
    // LISTAR LANÇAMENTOS
    // ==================================================

    private function listarLancamentos(
        int $empresaId,
        string $dataInicio,
        string $dataFim,
        int $statusContabilizado,
        ?int $filialId,
        ?int $diarioId
    ): array {

        $sql = "
            SELECT
                lancamento.id,
                lancamento.numero,
                lancamento.numero_documento,
                lancamento.referencia,
                lancamento.data_lancamento,
                lancamento.data_documento,
                lancamento.descricao,
                lancamento.origem,
                lancamento.origem_id,
                lancamento.total_debito,
                lancamento.total_credito,
                lancamento.contabilizado_em,

                diario.id AS diario_id,
                diario.codigo AS diario_codigo,
                diario.nome AS diario_nome,
                diario.tipo AS diario_tipo,

                filial.id AS filial_id,
                filial.codigo AS filial_codigo,
                filial.nome AS filial_nome,

                usuario.nome AS contabilizado_por_nome

            FROM lancamentos_contabilisticos lancamento

            INNER JOIN diarios_contabilisticos diario
                ON diario.id =
                    lancamento.diario_id

            LEFT JOIN filiais filial
                ON filial.id =
                    lancamento.filial_id

            LEFT JOIN usuarios usuario
                ON usuario.id =
                    lancamento.contabilizado_por

            WHERE lancamento.empresa_id =
                :empresa_id

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

        if ($diarioId !== null) {
            $sql .= "
                AND lancamento.diario_id =
                    :diario_id
            ";

            $params[':diario_id'] =
                $diarioId;
        }

        $sql .= "
            ORDER BY
                lancamento.data_lancamento ASC,
                diario.codigo ASC,
                lancamento.numero ASC,
                lancamento.id ASC
        ";

        $stmt =
            $this->pdo->prepare(
                $sql
            );

        $stmt->execute(
            $params
        );

        $lancamentos =
            $stmt->fetchAll(
                PDO::FETCH_ASSOC
            );

        if (!$lancamentos) {
            return [];
        }

        $ids =
            array_map(
                static fn(array $linha): int =>
                (int)$linha['id'],
                $lancamentos
            );

        $itensPorLancamento =
            $this->listarItensPorLancamentos(
                $ids
            );

        foreach ($lancamentos as &$lancamento) {
            $id =
                (int)$lancamento['id'];

            $lancamento['itens'] =
                $itensPorLancamento[$id]
                ?? [];
        }

        unset($lancamento);

        return $lancamentos;
    }

    // ==================================================
    // LISTAR ITENS DOS LANÇAMENTOS
    // ==================================================

    private function listarItensPorLancamentos(
        array $lancamentoIds
    ): array {

        if (!$lancamentoIds) {
            return [];
        }

        $placeholders =
            implode(
                ',',
                array_fill(
                    0,
                    count($lancamentoIds),
                    '?'
                )
            );

        $stmt =
            $this->pdo->prepare("
                SELECT
                    item.id,
                    item.lancamento_id,
                    item.descricao,
                    item.debito,
                    item.credito,
                    item.ordem,

                    conta.id AS conta_id,
                    conta.codigo AS conta_codigo,
                    conta.nome AS conta_nome,
                    conta.natureza AS conta_natureza,
                    conta.grupo AS conta_grupo,

                    cliente.nome AS cliente_nome,
                    fornecedor.nome AS fornecedor_nome,

                    centro.codigo AS centro_custo_codigo,
                    centro.nome AS centro_custo_nome

                FROM lancamento_contabilistico_itens item

                INNER JOIN contas_contabilisticas conta
                    ON conta.id =
                        item.conta_contabil_id

                LEFT JOIN clientes cliente
                    ON cliente.id =
                        item.cliente_id

                LEFT JOIN fornecedores fornecedor
                    ON fornecedor.id =
                        item.fornecedor_id

                LEFT JOIN centros_custo centro
                    ON centro.id =
                        item.centro_custo_id

                WHERE item.lancamento_id
                    IN ({$placeholders})

                ORDER BY
                    item.lancamento_id ASC,
                    item.ordem ASC,
                    item.id ASC
            ");

        $stmt->execute(
            $lancamentoIds
        );

        $linhas =
            $stmt->fetchAll(
                PDO::FETCH_ASSOC
            );

        $resultado = [];

        foreach ($linhas as $linha) {

            $lancamentoId =
                (int)$linha['lancamento_id'];

            $resultado[$lancamentoId][] = [
                'id' =>
                (int)$linha['id'],

                'conta_id' =>
                (int)$linha['conta_id'],

                'conta_codigo' =>
                (string)$linha['conta_codigo'],

                'conta_nome' =>
                (string)$linha['conta_nome'],

                'conta_natureza' =>
                (string)$linha['conta_natureza'],

                'conta_grupo' =>
                (string)$linha['conta_grupo'],

                'descricao' =>
                $linha['descricao'],

                'cliente_nome' =>
                $linha['cliente_nome'],

                'fornecedor_nome' =>
                $linha['fornecedor_nome'],

                'centro_custo_codigo' =>
                $linha['centro_custo_codigo'],

                'centro_custo_nome' =>
                $linha['centro_custo_nome'],

                'debito' =>
                round(
                    (float)$linha['debito'],
                    2
                ),

                'credito' =>
                round(
                    (float)$linha['credito'],
                    2
                )
            ];
        }

        return $resultado;
    }

    // ==================================================
    // MONTAR RESULTADO
    // ==================================================

    private function montarResultado(
        array $lancamentos,
        string $dataInicio,
        string $dataFim,
        ?int $filialId,
        ?int $diarioId
    ): array {

        $resultado = [];

        $totalDebitos =
            0.0;

        $totalCreditos =
            0.0;

        foreach ($lancamentos as $lancamento) {

            $totalDebitos +=
                (float)$lancamento['total_debito'];

            $totalCreditos +=
                (float)$lancamento['total_credito'];

            $resultado[] = [
                'id' =>
                (int)$lancamento['id'],

                'numero' =>
                (int)$lancamento['numero'],

                'numero_documento' =>
                $lancamento['numero_documento'],

                'referencia' =>
                $lancamento['referencia'],

                'data_lancamento' =>
                $lancamento['data_lancamento'],

                'data_documento' =>
                $lancamento['data_documento'],

                'descricao' =>
                $lancamento['descricao'],

                'origem' =>
                $lancamento['origem'],

                'origem_id' =>
                $lancamento['origem_id'] !== null
                    ? (int)$lancamento['origem_id']
                    : null,

                'total_debito' =>
                round(
                    (float)$lancamento['total_debito'],
                    2
                ),

                'total_credito' =>
                round(
                    (float)$lancamento['total_credito'],
                    2
                ),

                'contabilizado_em' =>
                $lancamento['contabilizado_em'],

                'contabilizado_por_nome' =>
                $lancamento['contabilizado_por_nome'],

                'diario' => [
                    'id' =>
                    (int)$lancamento['diario_id'],

                    'codigo' =>
                    (string)$lancamento['diario_codigo'],

                    'nome' =>
                    (string)$lancamento['diario_nome'],

                    'tipo' =>
                    (string)$lancamento['diario_tipo']
                ],

                'filial' =>
                $lancamento['filial_id'] !== null
                    ? [
                        'id' =>
                        (int)$lancamento['filial_id'],

                        'codigo' =>
                        $lancamento['filial_codigo'],

                        'nome' =>
                        $lancamento['filial_nome']
                    ]
                    : null,

                'itens' =>
                $lancamento['itens']
            ];
        }

        $totalDebitos =
            round(
                $totalDebitos,
                2
            );

        $totalCreditos =
            round(
                $totalCreditos,
                2
            );

        return [
            'filtros' => [
                'data_inicio' =>
                $dataInicio,

                'data_fim' =>
                $dataFim,

                'filial_id' =>
                $filialId,

                'diario_id' =>
                $diarioId
            ],

            'lancamentos' =>
            $resultado,

            'quantidade_lancamentos' =>
            count($resultado),

            'total_debitos' =>
            $totalDebitos,

            'total_creditos' =>
            $totalCreditos,

            'diferenca' =>
            round(
                $totalDebitos
                    - $totalCreditos,
                2
            ),

            'equilibrado' =>
            abs(
                $totalDebitos
                    - $totalCreditos
            ) <= 0.009
        ];
    }

    // ==================================================
    // LISTAR DIÁRIOS
    // ==================================================

    public function listarDiarios(
        int $empresaId
    ): array {

        $this->validarEmpresa(
            $empresaId
        );

        $stmt =
            $this->pdo->prepare("
                SELECT
                    diario.id,
                    diario.codigo,
                    diario.nome,
                    diario.tipo

                FROM diarios_contabilisticos diario

                INNER JOIN status
                    ON status.id =
                        diario.status_id

                WHERE diario.empresa_id =
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
                    diario.codigo ASC,
                    diario.nome ASC
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
    // LISTAR FILIAIS
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
    // VALIDAR DIÁRIO
    // ==================================================

    private function validarDiario(
        int $diarioId,
        int $empresaId
    ): void {

        if ($diarioId <= 0) {
            throw new RuntimeException(
                'Diário contabilístico inválido.'
            );
        }

        $stmt =
            $this->pdo->prepare("
                SELECT id

                FROM diarios_contabilisticos

                WHERE id =
                    :id

                  AND empresa_id =
                    :empresa_id

                LIMIT 1
            ");

        $stmt->execute([
            ':id' =>
            $diarioId,

            ':empresa_id' =>
            $empresaId
        ]);

        if (!$stmt->fetchColumn()) {
            throw new RuntimeException(
                'O diário selecionado não pertence à empresa.'
            );
        }
    }

    // ==================================================
    // VALIDAR FILIAL
    // ==================================================

    private function validarFilial(
        int $filialId,
        int $empresaId
    ): void {

        if ($filialId <= 0) {
            throw new RuntimeException(
                'Filial inválida.'
            );
        }

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
                'Empresa inválida para gerar o Diário Geral.'
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
