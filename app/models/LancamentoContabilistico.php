<?php

declare(strict_types=1);

require_once __DIR__
    . '/../services/AuditoriaService.php';

require_once __DIR__
    . '/../services/LogService.php';

final class LancamentoContabilistico
{
    private PDO $pdo;

    private AuditoriaService $auditoria;

    private LogService $log;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;

        $this->pdo->setAttribute(
            PDO::ATTR_ERRMODE,
            PDO::ERRMODE_EXCEPTION
        );

        $this->auditoria =
            new AuditoriaService($pdo);

        $this->log =
            new LogService($pdo);
    }

    // ==================================================
    // LISTAR
    // ==================================================

    public function listar(
        int $empresaId,
        ?int $filialId = null,
        array $filtros = []
    ): array {

        $this->validarEmpresa(
            $empresaId
        );

        $sql = "
            SELECT
                lancamento.id,
                lancamento.empresa_id,
                lancamento.filial_id,
                lancamento.exercicio_id,
                lancamento.periodo_id,
                lancamento.diario_id,
                lancamento.numero,
                lancamento.numero_documento,
                lancamento.referencia,
                lancamento.data_lancamento,
                lancamento.data_documento,
                lancamento.descricao,
                lancamento.origem,
                lancamento.origem_id,
                lancamento.status_id,
                lancamento.total_debito,
                lancamento.total_credito,
                lancamento.contabilizado_em,
                lancamento.anulado_em,
                lancamento.motivo_anulacao,
                lancamento.criado_em,
                lancamento.atualizado_em,

                filial.nome AS filial_nome,

                exercicio.codigo
                    AS exercicio_codigo,

                periodo.numero
                    AS periodo_numero,

                periodo.nome
                    AS periodo_nome,

                diario.codigo
                    AS diario_codigo,

                diario.nome
                    AS diario_nome,

                status.nome
                    AS status_nome,

                (
                    SELECT COUNT(*)

                    FROM lancamento_contabilistico_itens item

                    WHERE item.lancamento_id =
                        lancamento.id
                ) AS quantidade_itens

            FROM lancamentos_contabilisticos lancamento

            LEFT JOIN filiais filial
                ON filial.id =
                    lancamento.filial_id

            INNER JOIN exercicios_contabilisticos exercicio
                ON exercicio.id =
                    lancamento.exercicio_id

            INNER JOIN periodos_contabilisticos periodo
                ON periodo.id =
                    lancamento.periodo_id

            INNER JOIN diarios_contabilisticos diario
                ON diario.id =
                    lancamento.diario_id

            INNER JOIN status
                ON status.id =
                    lancamento.status_id

               AND status.tipo =
                    'lancamento_contabilistico'

            WHERE lancamento.empresa_id =
                :empresa_id
        ";

        $params = [
            ':empresa_id' =>
            $empresaId
        ];

        if ($filialId !== null) {
            $sql .= "
                AND (
                    lancamento.filial_id =
                        :filial_id

                    OR lancamento.filial_id
                        IS NULL
                )
            ";

            $params[':filial_id'] =
                $filialId;
        }

        if (
            !empty($filtros['exercicio_id'])
        ) {
            $sql .= "
                AND lancamento.exercicio_id =
                    :exercicio_id
            ";

            $params[':exercicio_id'] =
                (int)$filtros['exercicio_id'];
        }

        if (
            !empty($filtros['periodo_id'])
        ) {
            $sql .= "
                AND lancamento.periodo_id =
                    :periodo_id
            ";

            $params[':periodo_id'] =
                (int)$filtros['periodo_id'];
        }

        if (
            !empty($filtros['diario_id'])
        ) {
            $sql .= "
                AND lancamento.diario_id =
                    :diario_id
            ";

            $params[':diario_id'] =
                (int)$filtros['diario_id'];
        }

        if (
            !empty($filtros['status_id'])
        ) {
            $sql .= "
                AND lancamento.status_id =
                    :status_id
            ";

            $params[':status_id'] =
                (int)$filtros['status_id'];
        }

        if (
            !empty($filtros['origem'])
        ) {
            $sql .= "
                AND lancamento.origem =
                    :origem
            ";

            $params[':origem'] =
                strtoupper(
                    trim(
                        (string)$filtros['origem']
                    )
                );
        }

        if (
            !empty($filtros['data_inicio'])
        ) {
            $sql .= "
                AND lancamento.data_lancamento >=
                    :data_inicio
            ";

            $params[':data_inicio'] =
                $filtros['data_inicio'];
        }

        if (
            !empty($filtros['data_fim'])
        ) {
            $sql .= "
                AND lancamento.data_lancamento <=
                    :data_fim
            ";

            $params[':data_fim'] =
                $filtros['data_fim'];
        }

        $sql .= "
            ORDER BY
                lancamento.data_lancamento DESC,
                lancamento.numero DESC,
                lancamento.id DESC
        ";

        $stmt =
            $this->pdo->prepare($sql);

        $stmt->execute($params);

        return $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );
    }

    // ==================================================
    // BUSCAR
    // ==================================================

    public function buscar(
        int $id,
        int $empresaId
    ): ?array {

        $this->validarEmpresa(
            $empresaId
        );

        if ($id <= 0) {
            throw new RuntimeException(
                'Lançamento contabilístico inválido.'
            );
        }

        $stmt = $this->pdo->prepare("
            SELECT
                lancamento.*,

                exercicio.codigo
                    AS exercicio_codigo,

                periodo.numero
                    AS periodo_numero,

                periodo.nome
                    AS periodo_nome,

                diario.codigo
                    AS diario_codigo,

                diario.nome
                    AS diario_nome,

                diario.tipo
                    AS diario_tipo,

                status.nome
                    AS status_nome,

                filial.nome
                    AS filial_nome

            FROM lancamentos_contabilisticos lancamento

            INNER JOIN exercicios_contabilisticos exercicio
                ON exercicio.id =
                    lancamento.exercicio_id

            INNER JOIN periodos_contabilisticos periodo
                ON periodo.id =
                    lancamento.periodo_id

            INNER JOIN diarios_contabilisticos diario
                ON diario.id =
                    lancamento.diario_id

            INNER JOIN status
                ON status.id =
                    lancamento.status_id

               AND status.tipo =
                    'lancamento_contabilistico'

            LEFT JOIN filiais filial
                ON filial.id =
                    lancamento.filial_id

            WHERE lancamento.id =
                :id

              AND lancamento.empresa_id =
                :empresa_id

            LIMIT 1
        ");

        $stmt->execute([
            ':id' =>
            $id,

            ':empresa_id' =>
            $empresaId
        ]);

        $lancamento =
            $stmt->fetch(
                PDO::FETCH_ASSOC
            );

        if (!$lancamento) {
            return null;
        }

        $lancamento['itens'] =
            $this->buscarItens(
                $id,
                $empresaId
            );

        return $lancamento;
    }

    // ==================================================
    // BUSCAR ITENS
    // ==================================================

    public function buscarItens(
        int $lancamentoId,
        int $empresaId
    ): array {

        $stmt = $this->pdo->prepare("
            SELECT
                item.id,
                item.lancamento_id,
                item.conta_contabil_id,
                item.centro_custo_id,
                item.cliente_id,
                item.fornecedor_id,
                item.descricao,
                item.debito,
                item.credito,
                item.ordem,
                item.criado_em,

                conta.codigo
                    AS conta_codigo,

                conta.nome
                    AS conta_nome,

                conta.tipo
                    AS conta_tipo,

                conta.natureza
                    AS conta_natureza,

                centro.nome
                    AS centro_custo_nome,

                cliente.nome
                    AS cliente_nome,

                fornecedor.nome
                    AS fornecedor_nome

            FROM lancamento_contabilistico_itens item

            INNER JOIN lancamentos_contabilisticos lancamento
                ON lancamento.id =
                    item.lancamento_id

            INNER JOIN contas_contabilisticas conta
                ON conta.id =
                    item.conta_contabil_id

            LEFT JOIN centros_custo centro
                ON centro.id =
                    item.centro_custo_id

            LEFT JOIN clientes cliente
                ON cliente.id =
                    item.cliente_id

            LEFT JOIN fornecedores fornecedor
                ON fornecedor.id =
                    item.fornecedor_id

            WHERE item.lancamento_id =
                :lancamento_id

              AND lancamento.empresa_id =
                :empresa_id

            ORDER BY
                item.ordem ASC,
                item.id ASC
        ");

        $stmt->execute([
            ':lancamento_id' =>
            $lancamentoId,

            ':empresa_id' =>
            $empresaId
        ]);

        return $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );
    }

    // ==================================================
    // GUARDAR
    // ==================================================

    public function guardar(
        array $dados
    ): int {

        $dados =
            $this->normalizarDados(
                $dados
            );

        $this->validarDadosGerais(
            $dados
        );

        $this->validarItens(
            $dados['itens'],
            $dados['empresa_id']
        );

        $totais =
            $this->calcularTotais(
                $dados['itens']
            );

        $this->validarBalanceamento(
            $totais
        );

        $this->validarContextoContabilistico(
            $dados
        );

        $this->pdo->beginTransaction();

        $antes = null;

        $novo =
            $dados['id'] === null;

        try {

            if (!$novo) {

                $antes =
                    $this->buscar(
                        $dados['id'],
                        $dados['empresa_id']
                    );

                if (!$antes) {
                    throw new RuntimeException(
                        'Lançamento contabilístico não encontrado.'
                    );
                }
            }

            if ($novo) {

                $lancamentoId =
                    $this->criar(
                        $dados,
                        $totais
                    );
            } else {

                $lancamentoId =
                    $this->atualizar(
                        $dados,
                        $totais
                    );

                $stmt =
                    $this->pdo->prepare("
                    DELETE FROM
                        lancamento_contabilistico_itens

                    WHERE lancamento_id =
                        :lancamento_id
                ");

                $stmt->execute([
                    ':lancamento_id' =>
                    $lancamentoId
                ]);
            }

            $this->inserirItens(
                $lancamentoId,
                $dados['itens']
            );

            $depois =
                $this->buscar(
                    $lancamentoId,
                    $dados['empresa_id']
                );

            if (!$depois) {
                throw new RuntimeException(
                    'Não foi possível carregar o lançamento após a gravação.'
                );
            }

            if ($novo) {

                $this->auditoria->insercao(
                    $dados['empresa_id'],
                    $dados['usuario_id'],
                    'lancamentos_contabilisticos',
                    $lancamentoId,
                    $this->dadosAuditoriaLancamento(
                        $depois
                    )
                );

                $this->log->registrar(
                    $dados['empresa_id'],
                    $dados['usuario_id'],
                    'LANCAMENTOS_CONTABILISTICOS',
                    'CRIAR',
                    'Criou o lançamento contabilístico nº '
                        . $depois['numero']
                        . '.',
                    $lancamentoId,
                    $depois['filial_id']
                        !== null
                        ? (int)$depois['filial_id']
                        : null
                );
            } else {

                $this->auditoria->atualizacao(
                    $dados['empresa_id'],
                    $dados['usuario_id'],
                    'lancamentos_contabilisticos',
                    $lancamentoId,
                    $this->dadosAuditoriaLancamento(
                        $antes
                    ),
                    $this->dadosAuditoriaLancamento(
                        $depois
                    )
                );

                $this->log->registrar(
                    $dados['empresa_id'],
                    $dados['usuario_id'],
                    'LANCAMENTOS_CONTABILISTICOS',
                    'EDITAR',
                    'Atualizou o lançamento contabilístico nº '
                        . $depois['numero']
                        . '.',
                    $lancamentoId,
                    $depois['filial_id']
                        !== null
                        ? (int)$depois['filial_id']
                        : null
                );
            }

            $this->pdo->commit();

            return $lancamentoId;
        } catch (Throwable $e) {

            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw $e;
        }
    }

    // ==================================================
    // CRIAR
    // ==================================================

    private function criar(
        array $dados,
        array $totais
    ): int {

        $statusRascunho =
            $this->statusId(
                'lancamento_contabilistico',
                'Rascunho'
            );

        $numero =
            $this->reservarNumeroDiario(
                $dados['diario_id'],
                $dados['empresa_id'],
                $dados['exercicio_id']
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
                :criado_por,
                :atualizado_por
            )
        ");

        $stmt->execute([
            ':empresa_id' =>
            $dados['empresa_id'],

            ':filial_id' =>
            $dados['filial_id'],

            ':exercicio_id' =>
            $dados['exercicio_id'],

            ':periodo_id' =>
            $dados['periodo_id'],

            ':diario_id' =>
            $dados['diario_id'],

            ':numero' =>
            $numero,

            ':numero_documento' =>
            $dados['numero_documento'],

            ':referencia' =>
            $dados['referencia'],

            ':data_lancamento' =>
            $dados['data_lancamento'],

            ':data_documento' =>
            $dados['data_documento'],

            ':descricao' =>
            $dados['descricao'],

            ':origem' =>
            $dados['origem'],

            ':origem_id' =>
            $dados['origem_id'],

            ':status_id' =>
            $statusRascunho,

            ':total_debito' =>
            $totais['debito'],

            ':total_credito' =>
            $totais['credito'],

            ':criado_por' =>
            $dados['usuario_id'],

            ':atualizado_por' =>
            $dados['usuario_id']
        ]);

        return (int)$this->pdo
            ->lastInsertId();
    }

    // ==================================================
    // ATUALIZAR
    // ==================================================

    private function atualizar(
        array $dados,
        array $totais
    ): int {

        $atual =
            $this->buscar(
                $dados['id'],
                $dados['empresa_id']
            );

        if (!$atual) {
            throw new RuntimeException(
                'Lançamento contabilístico não encontrado.'
            );
        }

        if (
            (int)$atual['diario_id']
            !== (int)$dados['diario_id']
        ) {
            throw new RuntimeException(
                'O diário de um lançamento já criado não pode ser alterado.'
            );
        }

        if (
            (int)$atual['exercicio_id']
            !== (int)$dados['exercicio_id']
        ) {
            throw new RuntimeException(
                'O exercício de um lançamento já criado não pode ser alterado.'
            );
        }

        if (
            !$this->statusEh(
                (int)$atual['status_id'],
                'lancamento_contabilistico',
                'Rascunho'
            )
        ) {
            throw new RuntimeException(
                'Somente lançamentos em rascunho podem ser editados.'
            );
        }

        if (
            $atual['origem']
            !== 'MANUAL'
        ) {
            throw new RuntimeException(
                'Lançamentos automáticos não podem ser editados manualmente.'
            );
        }

        $stmt = $this->pdo->prepare("
            UPDATE lancamentos_contabilisticos

            SET
                filial_id =
                    :filial_id,

                exercicio_id =
                    :exercicio_id,

                periodo_id =
                    :periodo_id,

                diario_id =
                    :diario_id,

                numero_documento =
                    :numero_documento,

                referencia =
                    :referencia,

                data_lancamento =
                    :data_lancamento,

                data_documento =
                    :data_documento,

                descricao =
                    :descricao,

                total_debito =
                    :total_debito,

                total_credito =
                    :total_credito,

                atualizado_por =
                    :atualizado_por

            WHERE id =
                :id

              AND empresa_id =
                :empresa_id
        ");

        $stmt->execute([
            ':filial_id' =>
            $dados['filial_id'],

            ':exercicio_id' =>
            $dados['exercicio_id'],

            ':periodo_id' =>
            $dados['periodo_id'],

            ':diario_id' =>
            $dados['diario_id'],

            ':numero_documento' =>
            $dados['numero_documento'],

            ':referencia' =>
            $dados['referencia'],

            ':data_lancamento' =>
            $dados['data_lancamento'],

            ':data_documento' =>
            $dados['data_documento'],

            ':descricao' =>
            $dados['descricao'],

            ':total_debito' =>
            $totais['debito'],

            ':total_credito' =>
            $totais['credito'],

            ':atualizado_por' =>
            $dados['usuario_id'],

            ':id' =>
            $dados['id'],

            ':empresa_id' =>
            $dados['empresa_id']
        ]);

        return $dados['id'];
    }

    // ==================================================
    // INSERIR ITENS
    // ==================================================

    private function inserirItens(
        int $lancamentoId,
        array $itens
    ): void {

        $stmt = $this->pdo->prepare("
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

        foreach (
            $itens as $indice => $item
        ) {
            $stmt->execute([
                ':lancamento_id' =>
                $lancamentoId,

                ':conta_contabil_id' =>
                $item['conta_contabil_id'],

                ':centro_custo_id' =>
                $item['centro_custo_id'],

                ':cliente_id' =>
                $item['cliente_id'],

                ':fornecedor_id' =>
                $item['fornecedor_id'],

                ':descricao' =>
                $item['descricao'],

                ':debito' =>
                $item['debito'],

                ':credito' =>
                $item['credito'],

                ':ordem' =>
                $indice + 1
            ]);
        }
    }

    // ==================================================
    // CONTABILIZAR
    // ==================================================

    public function contabilizar(
        int $id,
        int $empresaId,
        int $usuarioId
    ): bool {

        $this->pdo->beginTransaction();

        try {

            $antes =
                $this->buscar(
                    $id,
                    $empresaId
                );

            if (!$antes) {
                throw new RuntimeException(
                    'Lançamento contabilístico não encontrado.'
                );
            }

            if (
                !$this->statusEh(
                    (int)$antes['status_id'],
                    'lancamento_contabilistico',
                    'Rascunho'
                )
            ) {
                throw new RuntimeException(
                    'Somente lançamentos em rascunho podem ser contabilizados.'
                );
            }

            $this->validarContextoContabilistico(
                $this->normalizarDados(
                    $antes
                )
            );

            $this->validarItens(
                $antes['itens'],
                $empresaId
            );

            $totais =
                $this->calcularTotais(
                    $antes['itens']
                );

            $this->validarBalanceamento(
                $totais
            );

            $statusContabilizado =
                $this->statusId(
                    'lancamento_contabilistico',
                    'Contabilizado'
                );

            $stmt =
                $this->pdo->prepare("
                UPDATE lancamentos_contabilisticos

                SET
                    status_id = :status_id,
                    total_debito = :total_debito,
                    total_credito = :total_credito,
                    contabilizado_em = NOW(),
                    contabilizado_por = :usuario_id,
                    atualizado_por = :usuario_id

                WHERE id = :id
                  AND empresa_id = :empresa_id
            ");

            $stmt->execute([
                ':status_id' =>
                $statusContabilizado,

                ':total_debito' =>
                $totais['debito'],

                ':total_credito' =>
                $totais['credito'],

                ':usuario_id' =>
                $usuarioId,

                ':id' =>
                $id,

                ':empresa_id' =>
                $empresaId
            ]);

            if ($stmt->rowCount() !== 1) {
                throw new RuntimeException(
                    'Não foi possível contabilizar o lançamento.'
                );
            }

            $depois =
                $this->buscar(
                    $id,
                    $empresaId
                );

            if (!$depois) {
                throw new RuntimeException(
                    'Não foi possível carregar o lançamento após a contabilização.'
                );
            }

            $this->auditoria->atualizacao(
                $empresaId,
                $usuarioId,
                'lancamentos_contabilisticos',
                $id,
                $this->dadosAuditoriaLancamento(
                    $antes
                ),
                $this->dadosAuditoriaLancamento(
                    $depois
                )
            );

            $this->log->registrar(
                $empresaId,
                $usuarioId,
                'LANCAMENTOS_CONTABILISTICOS',
                'CONTABILIZAR',
                'Contabilizou o lançamento nº '
                    . $depois['numero']
                    . '.',
                $id,
                $depois['filial_id']
                    !== null
                    ? (int)$depois['filial_id']
                    : null
            );

            $this->pdo->commit();

            return true;
        } catch (Throwable $e) {

            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw $e;
        }
    }

    // ==================================================
    // ESTORNAR LANÇAMENTO
    // ==================================================

    public function estornar(
        int $id,
        int $empresaId,
        int $usuarioId,
        string $motivo,
        string $dataEstorno
    ): int {

        $motivo =
            trim($motivo);

        if ($motivo === '') {
            throw new RuntimeException(
                'Informe o motivo do estorno.'
            );
        }

        $this->validarData(
            $dataEstorno,
            'Data do estorno'
        );

        if ($usuarioId <= 0) {
            throw new RuntimeException(
                'Utilizador não autenticado.'
            );
        }

        $this->pdo->beginTransaction();

        try {

            // Bloquear o lançamento original
            $stmt = $this->pdo->prepare("
            SELECT
                lancamento.*

            FROM lancamentos_contabilisticos lancamento

            WHERE lancamento.id =
                :id

              AND lancamento.empresa_id =
                :empresa_id

            FOR UPDATE
        ");

            $stmt->execute([
                ':id' =>
                $id,

                ':empresa_id' =>
                $empresaId
            ]);

            $original =
                $stmt->fetch(
                    PDO::FETCH_ASSOC
                );

            if (!$original) {
                throw new RuntimeException(
                    'Lançamento contabilístico não encontrado.'
                );
            }

            if (
                !$this->statusEh(
                    (int)$original['status_id'],
                    'lancamento_contabilistico',
                    'Contabilizado'
                )
            ) {
                throw new RuntimeException(
                    'Somente lançamentos contabilizados podem ser estornados.'
                );
            }

            if (
                strtoupper(
                    (string)$original['origem']
                ) === 'ESTORNO'
            ) {
                throw new RuntimeException(
                    'Um lançamento de estorno não pode ser estornado diretamente.'
                );
            }

            // Impedir estorno duplicado
            $stmt = $this->pdo->prepare("
            SELECT id

            FROM lancamentos_contabilisticos

            WHERE lancamento_estornado_id =
                :lancamento_id

            LIMIT 1
        ");

            $stmt->execute([
                ':lancamento_id' =>
                $id
            ]);

            if ($stmt->fetchColumn()) {
                throw new RuntimeException(
                    'Este lançamento já possui um estorno contabilístico.'
                );
            }

            $contexto =
                $this->obterContextoDataEstorno(
                    $empresaId,
                    $dataEstorno
                );

            $statusContabilizado =
                $this->statusId(
                    'lancamento_contabilistico',
                    'Contabilizado'
                );

            $statusAnulado =
                $this->statusId(
                    'lancamento_contabilistico',
                    'Anulado'
                );

            $numero =
                $this->reservarNumeroDiario(
                    (int)$original['diario_id'],
                    $empresaId,
                    (int)$contexto['exercicio_id']
                );

            $descricaoEstorno =
                'Estorno do lançamento '
                . $original['numero']
                . ' — '
                . $motivo;

            // Criar cabeçalho do estorno
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
                lancamento_estornado_id,
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
                'ESTORNO',
                :origem_id,
                :lancamento_estornado_id,
                :status_id,
                :total_debito,
                :total_credito,
                NOW(),
                :contabilizado_por,
                :criado_por,
                :atualizado_por
            )
        ");

            $stmt->execute([
                ':empresa_id' =>
                $empresaId,

                ':filial_id' =>
                $original['filial_id'],

                ':exercicio_id' =>
                $contexto['exercicio_id'],

                ':periodo_id' =>
                $contexto['periodo_id'],

                ':diario_id' =>
                $original['diario_id'],

                ':numero' =>
                $numero,

                ':numero_documento' =>
                $original['numero_documento'],

                ':referencia' =>
                'ESTORNO-'
                    . $original['id'],

                ':data_lancamento' =>
                $dataEstorno,

                ':data_documento' =>
                $original['data_documento'],

                ':descricao' =>
                $descricaoEstorno,

                ':origem_id' =>
                $original['id'],

                ':lancamento_estornado_id' =>
                $original['id'],

                ':status_id' =>
                $statusContabilizado,

                // Os totais permanecem iguais,
                // apenas as posições são invertidas
                ':total_debito' =>
                $original['total_credito'],

                ':total_credito' =>
                $original['total_debito'],

                ':contabilizado_por' =>
                $usuarioId,

                ':criado_por' =>
                $usuarioId,

                ':atualizado_por' =>
                $usuarioId
            ]);

            $estornoId =
                (int)$this->pdo
                    ->lastInsertId();

            // Buscar itens do original
            $stmt = $this->pdo->prepare("
            SELECT
                conta_contabil_id,
                centro_custo_id,
                cliente_id,
                fornecedor_id,
                descricao,
                debito,
                credito,
                ordem

            FROM lancamento_contabilistico_itens

            WHERE lancamento_id =
                :lancamento_id

            ORDER BY
                ordem ASC,
                id ASC
        ");

            $stmt->execute([
                ':lancamento_id' =>
                $original['id']
            ]);

            $itens =
                $stmt->fetchAll(
                    PDO::FETCH_ASSOC
                );

            if (count($itens) < 2) {
                throw new RuntimeException(
                    'O lançamento original não possui itens contabilísticos válidos.'
                );
            }

            $antesOriginal =
                $this->buscar(
                    $id,
                    $empresaId
                );

            if (!$antesOriginal) {
                throw new RuntimeException(
                    'Não foi possível carregar o lançamento original.'
                );
            }

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

            foreach ($itens as $item) {

                $descricaoItem =
                    'Estorno: '
                    . (
                        trim(
                            (string)(
                                $item['descricao']
                                ?? ''
                            )
                        ) !== ''
                        ? $item['descricao']
                        : $original['descricao']
                    );

                $stmtItem->execute([
                    ':lancamento_id' =>
                    $estornoId,

                    ':conta_contabil_id' =>
                    $item['conta_contabil_id'],

                    ':centro_custo_id' =>
                    $item['centro_custo_id'],

                    ':cliente_id' =>
                    $item['cliente_id'],

                    ':fornecedor_id' =>
                    $item['fornecedor_id'],

                    ':descricao' =>
                    $descricaoItem,

                    // Inverter débito e crédito
                    ':debito' =>
                    $item['credito'],

                    ':credito' =>
                    $item['debito'],

                    ':ordem' =>
                    $item['ordem']
                ]);
            }

            // Marcar o lançamento original como anulado
            $stmt = $this->pdo->prepare("
            UPDATE lancamentos_contabilisticos

            SET
                status_id =
                    :status_id,

                anulado_em =
                    NOW(),

                anulado_por =
                    :usuario_id,

                motivo_anulacao =
                    :motivo,

                atualizado_por =
                    :atualizado_por

            WHERE id =
                :id

              AND empresa_id =
                :empresa_id
        ");

            $stmt->execute([
                ':status_id' =>
                $statusAnulado,

                ':usuario_id' =>
                $usuarioId,

                ':motivo' =>
                $motivo,

                ':atualizado_por' =>
                $usuarioId,

                ':id' =>
                $original['id'],

                ':empresa_id' =>
                $empresaId
            ]);

            $originalDepois =
                $this->buscar(
                    $id,
                    $empresaId
                );

            $estornoDepois =
                $this->buscar(
                    $estornoId,
                    $empresaId
                );

            $this->auditoria->atualizacao(
                $empresaId,
                $usuarioId,
                'lancamentos_contabilisticos',
                $id,
                $this->dadosAuditoriaLancamento(
                    $antesOriginal
                ),
                $this->dadosAuditoriaLancamento(
                    $originalDepois
                )
            );

            $this->auditoria->insercao(
                $empresaId,
                $usuarioId,
                'lancamentos_contabilisticos',
                $estornoId,
                $this->dadosAuditoriaLancamento(
                    $estornoDepois
                )
            );

            $this->log->registrar(
                $empresaId,
                $usuarioId,
                'LANCAMENTOS_CONTABILISTICOS',
                'ESTORNAR',
                'Estornou o lançamento nº '
                    . $antesOriginal['numero']
                    . ' através do lançamento nº '
                    . $estornoDepois['numero']
                    . '.',
                $estornoId,
                $estornoDepois['filial_id']
                    !== null
                    ? (int)$estornoDepois['filial_id']
                    : null
            );

            $this->pdo->commit();

            return $estornoId;
        } catch (Throwable $e) {

            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw $e;
        }
    }

    // ==================================================
    // CONTEXTO DA DATA DE ESTORNO
    // ==================================================

    private function obterContextoDataEstorno(
        int $empresaId,
        string $dataEstorno
    ): array {

        $stmt = $this->pdo->prepare("
        SELECT
            exercicio.id AS exercicio_id,
            periodo.id AS periodo_id,

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

          AND :data_estorno BETWEEN
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

            ':data_estorno' =>
            $dataEstorno,

            ':data_periodo' =>
            $dataEstorno
        ]);

        $contexto =
            $stmt->fetch(
                PDO::FETCH_ASSOC
            );

        if (!$contexto) {
            throw new RuntimeException(
                'Não existe exercício e período contabilístico para a data do estorno.'
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
                'O exercício da data do estorno não está aberto.'
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
                'O período da data do estorno não está aberto.'
            );
        }

        return $contexto;
    }

    // ==================================================
    // ELIMINAR
    // ==================================================

    public function eliminar(
        int $id,
        int $empresaId,
        int $usuarioId
    ): bool {

        $lancamento =
            $this->buscar(
                $id,
                $empresaId
            );

        if (!$lancamento) {
            throw new RuntimeException(
                'Lançamento contabilístico não encontrado.'
            );
        }

        if (
            !$this->statusEh(
                (int)$lancamento['status_id'],
                'lancamento_contabilistico',
                'Rascunho'
            )
        ) {
            throw new RuntimeException(
                'Somente lançamentos em rascunho podem ser eliminados.'
            );
        }

        if (
            $lancamento['origem']
            !== 'MANUAL'
        ) {
            throw new RuntimeException(
                'Lançamentos automáticos não podem ser eliminados manualmente.'
            );
        }

        $this->pdo->beginTransaction();

        try {

            $stmt =
                $this->pdo->prepare("
                DELETE FROM lancamentos_contabilisticos

                WHERE id = :id
                  AND empresa_id = :empresa_id
            ");

            $stmt->execute([
                ':id' =>
                $id,

                ':empresa_id' =>
                $empresaId
            ]);

            if ($stmt->rowCount() !== 1) {
                throw new RuntimeException(
                    'Não foi possível eliminar o lançamento.'
                );
            }

            $this->auditoria->exclusao(
                $empresaId,
                $usuarioId,
                'lancamentos_contabilisticos',
                $id,
                $this->dadosAuditoriaLancamento(
                    $lancamento
                )
            );

            $this->log->registrar(
                $empresaId,
                $usuarioId,
                'LANCAMENTOS_CONTABILISTICOS',
                'ELIMINAR',
                'Eliminou o lançamento contabilístico nº '
                    . $lancamento['numero']
                    . '.',
                $id,
                $lancamento['filial_id']
                    !== null
                    ? (int)$lancamento['filial_id']
                    : null
            );

            $this->pdo->commit();

            return true;
        } catch (Throwable $e) {

            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw $e;
        }
    }

    // ==================================================
    // OPÇÕES
    // ==================================================

    public function listarExercicios(
        int $empresaId
    ): array {

        $stmt = $this->pdo->prepare("
            SELECT
                exercicio.id,
                exercicio.codigo,
                exercicio.descricao,
                exercicio.data_inicio,
                exercicio.data_fim,
                exercicio.status_id,
                status.nome AS status_nome

            FROM exercicios_contabilisticos exercicio

            INNER JOIN status
                ON status.id =
                    exercicio.status_id

               AND status.tipo =
                    'exercicio_contabilistico'

            WHERE exercicio.empresa_id =
                :empresa_id

              AND LOWER(status.nome) = 'aberto'

            ORDER BY exercicio.data_inicio DESC
        ");

        $stmt->execute([
            ':empresa_id' =>
            $empresaId
        ]);

        return $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );
    }

    public function listarPeriodos(
        int $empresaId,
        int $exercicioId
    ): array {

        $stmt = $this->pdo->prepare("
            SELECT
                periodo.id,
                periodo.numero,
                periodo.nome,
                periodo.data_inicio,
                periodo.data_fim,
                periodo.status_id,
                status.nome AS status_nome

            FROM periodos_contabilisticos periodo

            INNER JOIN status
                ON status.id =
                    periodo.status_id

               AND status.tipo =
                    'periodo_contabilistico'

            WHERE periodo.empresa_id =
                :empresa_id

              AND periodo.exercicio_id =
                :exercicio_id

            ORDER BY periodo.numero ASC
        ");

        $stmt->execute([
            ':empresa_id' =>
            $empresaId,

            ':exercicio_id' =>
            $exercicioId
        ]);

        return $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );
    }

    public function listarDiarios(
        int $empresaId
    ): array {

        $stmt = $this->pdo->prepare("
            SELECT
                diario.id,
                diario.codigo,
                diario.nome,
                diario.tipo,
                diario.permite_manual,
                diario.numeracao_automatica,
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

              AND LOWER(status.nome) =
                    'activo'

            ORDER BY diario.codigo ASC
        ");

        $stmt->execute([
            ':empresa_id' =>
            $empresaId
        ]);

        return $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );
    }

    public function listarContas(
        int $empresaId
    ): array {

        $stmt = $this->pdo->prepare("
            SELECT
                conta.id,
                conta.codigo,
                conta.nome,
                conta.natureza,
                conta.grupo,
                conta.exige_centro_custo,
                conta.exige_entidade,
                conta.status_id

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

              AND conta.aceita_lancamento = 1

              AND LOWER(status.nome) =
                    'activa'

            ORDER BY conta.codigo ASC
        ");

        $stmt->execute([
            ':empresa_id' =>
            $empresaId
        ]);

        return $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );
    }

    public function listarStatus(): array
    {
        $stmt = $this->pdo->prepare("
            SELECT
                id,
                tipo,
                nome,
                descricao

            FROM status

            WHERE tipo =
                'lancamento_contabilistico'

            ORDER BY id ASC
        ");

        $stmt->execute();

        return $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );
    }

    // ==================================================
    // NORMALIZAÇÃO
    // ==================================================

    private function normalizarDados(
        array $dados
    ): array {

        $itens =
            $dados['itens']
            ?? [];

        if (is_string($itens)) {
            $itens =
                json_decode(
                    $itens,
                    true
                );

            if (
                json_last_error()
                !== JSON_ERROR_NONE
            ) {
                throw new RuntimeException(
                    'A lista de itens do lançamento é inválida.'
                );
            }
        }

        return [
            'id' =>
            !empty($dados['id'])
                ? (int)$dados['id']
                : null,

            'empresa_id' =>
            (int)(
                $dados['empresa_id']
                ?? 0
            ),

            'filial_id' =>
            !empty($dados['filial_id'])
                ? (int)$dados['filial_id']
                : null,

            'usuario_id' =>
            (int)(
                $dados['usuario_id']
                ?? 0
            ),

            'exercicio_id' =>
            (int)(
                $dados['exercicio_id']
                ?? 0
            ),

            'periodo_id' =>
            (int)(
                $dados['periodo_id']
                ?? 0
            ),

            'diario_id' =>
            (int)(
                $dados['diario_id']
                ?? 0
            ),

            'numero_documento' =>
            $this->textoOuNull(
                $dados['numero_documento']
                    ?? null
            ),

            'referencia' =>
            $this->textoOuNull(
                $dados['referencia']
                    ?? null
            ),

            'data_lancamento' =>
            trim(
                (string)(
                    $dados['data_lancamento']
                    ?? ''
                )
            ),

            'data_documento' =>
            $this->dataOuNull(
                $dados['data_documento']
                    ?? null
            ),

            'descricao' =>
            trim(
                (string)(
                    $dados['descricao']
                    ?? ''
                )
            ),

            'origem' =>
            strtoupper(
                trim(
                    (string)(
                        $dados['origem']
                        ?? 'MANUAL'
                    )
                )
            ),

            'origem_id' =>
            !empty($dados['origem_id'])
                ? (int)$dados['origem_id']
                : null,

            'itens' =>
            is_array($itens)
                ? array_values($itens)
                : []
        ];
    }

    private function normalizarItem(
        array $item
    ): array {

        return [
            'conta_contabil_id' =>
            (int)(
                $item['conta_contabil_id']
                ?? $item['conta_contabilistica_id']
                ?? 0
            ),

            'centro_custo_id' =>
            !empty($item['centro_custo_id'])
                ? (int)$item['centro_custo_id']
                : null,

            'cliente_id' =>
            !empty($item['cliente_id'])
                ? (int)$item['cliente_id']
                : null,

            'fornecedor_id' =>
            !empty($item['fornecedor_id'])
                ? (int)$item['fornecedor_id']
                : null,

            'descricao' =>
            $this->textoOuNull(
                $item['descricao']
                    ?? null
            ),

            'debito' =>
            $this->decimal(
                $item['debito']
                    ?? 0
            ),

            'credito' =>
            $this->decimal(
                $item['credito']
                    ?? 0
            )
        ];
    }

    // ==================================================
    // VALIDAÇÕES
    // ==================================================

    private function validarDadosGerais(
        array $dados
    ): void {

        $this->validarEmpresa(
            $dados['empresa_id']
        );

        if ($dados['usuario_id'] <= 0) {
            throw new RuntimeException(
                'Utilizador não autenticado.'
            );
        }

        if ($dados['exercicio_id'] <= 0) {
            throw new RuntimeException(
                'Seleccione o exercício contabilístico.'
            );
        }

        if ($dados['periodo_id'] <= 0) {
            throw new RuntimeException(
                'Seleccione o período contabilístico.'
            );
        }

        if ($dados['diario_id'] <= 0) {
            throw new RuntimeException(
                'Seleccione o diário contabilístico.'
            );
        }

        $this->validarData(
            $dados['data_lancamento'],
            'Data do lançamento'
        );

        if ($dados['descricao'] === '') {
            throw new RuntimeException(
                'Informe a descrição do lançamento.'
            );
        }

        if (
            !in_array(
                $dados['origem'],
                [
                    'MANUAL',
                    'VENDA',
                    'COMPRA',
                    'RECEBIMENTO',
                    'PAGAMENTO',
                    'CAIXA',
                    'BANCO',
                    'STOCK',
                    'SALARIO',
                    'IMPOSTO',
                    'ABERTURA',
                    'ENCERRAMENTO'
                ],
                true
            )
        ) {
            throw new RuntimeException(
                'Origem do lançamento inválida.'
            );
        }

        if (
            $dados['origem'] !== 'MANUAL'
            && empty($dados['origem_id'])
        ) {
            throw new RuntimeException(
                'Informe o identificador da origem do lançamento.'
            );
        }
    }

    private function validarItens(
        array &$itens,
        int $empresaId
    ): void {

        if (count($itens) < 2) {
            throw new RuntimeException(
                'Adicione pelo menos dois itens ao lançamento.'
            );
        }

        foreach (
            $itens as $indice => $item
        ) {
            if (!is_array($item)) {
                throw new RuntimeException(
                    'Item contabilístico inválido.'
                );
            }

            $item =
                $this->normalizarItem(
                    $item
                );

            $numero =
                $indice + 1;

            if (
                $item['conta_contabil_id']
                <= 0
            ) {
                throw new RuntimeException(
                    "Seleccione a conta contabilística do item {$numero}."
                );
            }

            if (
                $item['debito'] <= 0
                && $item['credito'] <= 0
            ) {
                throw new RuntimeException(
                    "Informe o débito ou crédito do item {$numero}."
                );
            }

            if (
                $item['debito'] > 0
                && $item['credito'] > 0
            ) {
                throw new RuntimeException(
                    "O item {$numero} não pode possuir débito e crédito simultaneamente."
                );
            }

            $this->validarContaItem(
                $item,
                $empresaId,
                $numero
            );

            $this->validarEntidadesItem(
                $item,
                $empresaId,
                $numero
            );

            $itens[$indice] =
                $item;
        }
    }

    private function validarContextoContabilistico(
        array $dados
    ): void {

        $stmt = $this->pdo->prepare("
            SELECT
                periodo.data_inicio,
                periodo.data_fim,
                periodo.exercicio_id,

                status_periodo.nome
                    AS periodo_status,

                status_exercicio.nome
                    AS exercicio_status,

                diario.tipo
                    AS diario_tipo,

                diario.permite_manual,

                status_diario.nome
                    AS diario_status

            FROM periodos_contabilisticos periodo

            INNER JOIN exercicios_contabilisticos exercicio
                ON exercicio.id =
                    periodo.exercicio_id

            INNER JOIN status status_periodo
                ON status_periodo.id =
                    periodo.status_id

               AND status_periodo.tipo =
                    'periodo_contabilistico'

            INNER JOIN status status_exercicio
                ON status_exercicio.id =
                    exercicio.status_id

               AND status_exercicio.tipo =
                    'exercicio_contabilistico'

            INNER JOIN diarios_contabilisticos diario
                ON diario.id =
                    :diario_id

               AND diario.empresa_id =
                    :empresa_diario

            INNER JOIN status status_diario
                ON status_diario.id =
                    diario.status_id

               AND status_diario.tipo =
                    'diario_contabilistico'

            WHERE periodo.id =
                :periodo_id

              AND periodo.empresa_id =
                :empresa_periodo

              AND exercicio.id =
                :exercicio_id

              AND exercicio.empresa_id =
                :empresa_exercicio

            LIMIT 1
        ");

        $stmt->execute([
            ':diario_id' =>
            $dados['diario_id'],

            ':empresa_diario' =>
            $dados['empresa_id'],

            ':periodo_id' =>
            $dados['periodo_id'],

            ':empresa_periodo' =>
            $dados['empresa_id'],

            ':exercicio_id' =>
            $dados['exercicio_id'],

            ':empresa_exercicio' =>
            $dados['empresa_id']
        ]);

        $contexto =
            $stmt->fetch(
                PDO::FETCH_ASSOC
            );

        if (!$contexto) {
            throw new RuntimeException(
                'O exercício, período ou diário selecionado é inválido.'
            );
        }

        if (
            mb_strtolower(
                (string)$contexto['exercicio_status']
            ) !== 'aberto'
        ) {
            throw new RuntimeException(
                'O exercício contabilístico não está aberto.'
            );
        }

        if (
            mb_strtolower(
                (string)$contexto['periodo_status']
            ) !== 'aberto'
        ) {
            throw new RuntimeException(
                'O período contabilístico não está aberto.'
            );
        }

        if (
            mb_strtolower(
                (string)$contexto['diario_status']
            ) !== 'activo'
        ) {
            throw new RuntimeException(
                'O diário contabilístico não está ativo.'
            );
        }

        if (
            $dados['origem'] === 'MANUAL'
            && (int)$contexto['permite_manual']
            !== 1
        ) {
            throw new RuntimeException(
                'O diário selecionado não permite lançamentos manuais.'
            );
        }

        if (
            $dados['data_lancamento']
            < $contexto['data_inicio']

            || $dados['data_lancamento']
            > $contexto['data_fim']
        ) {
            throw new RuntimeException(
                'A data do lançamento está fora do período selecionado.'
            );
        }
    }

    private function validarContaItem(
        array $item,
        int $empresaId,
        int $numero
    ): void {

        $stmt = $this->pdo->prepare("
            SELECT
                conta.tipo,
                conta.aceita_lancamento,
                conta.exige_centro_custo,
                conta.exige_entidade,
                status.nome AS status_nome

            FROM contas_contabilisticas conta

            INNER JOIN status
                ON status.id =
                    conta.status_id

               AND status.tipo =
                    'conta_contabilistica'

            WHERE conta.id =
                :id

              AND conta.empresa_id =
                :empresa_id

            LIMIT 1
        ");

        $stmt->execute([
            ':id' =>
            $item['conta_contabil_id'],

            ':empresa_id' =>
            $empresaId
        ]);

        $conta =
            $stmt->fetch(
                PDO::FETCH_ASSOC
            );

        if (!$conta) {
            throw new RuntimeException(
                "Conta contabilística inválida no item {$numero}."
            );
        }

        if (
            $conta['tipo'] !== 'ANALITICA'
            || (int)$conta['aceita_lancamento']
            !== 1
        ) {
            throw new RuntimeException(
                "A conta do item {$numero} não aceita lançamentos."
            );
        }

        if (
            mb_strtolower(
                (string)$conta['status_nome']
            ) !== 'activa'
        ) {
            throw new RuntimeException(
                "A conta do item {$numero} não está ativa."
            );
        }

        if (
            (int)$conta['exige_centro_custo']
            === 1
            && empty($item['centro_custo_id'])
        ) {
            throw new RuntimeException(
                "Informe o centro de custo do item {$numero}."
            );
        }

        if (
            (int)$conta['exige_entidade']
            === 1
            && empty($item['cliente_id'])
            && empty($item['fornecedor_id'])
        ) {
            throw new RuntimeException(
                "Informe o cliente ou fornecedor do item {$numero}."
            );
        }
    }

    private function validarEntidadesItem(
        array $item,
        int $empresaId,
        int $numero
    ): void {

        if (
            !empty($item['cliente_id'])
            && !empty($item['fornecedor_id'])
        ) {
            throw new RuntimeException(
                "O item {$numero} não pode possuir cliente e fornecedor simultaneamente."
            );
        }

        $validacoes = [
            [
                'id' =>
                $item['centro_custo_id'],

                'tabela' =>
                'centros_custo',

                'mensagem' =>
                "Centro de custo inválido no item {$numero}."
            ],
            [
                'id' =>
                $item['cliente_id'],

                'tabela' =>
                'clientes',

                'mensagem' =>
                "Cliente inválido no item {$numero}."
            ],
            [
                'id' =>
                $item['fornecedor_id'],

                'tabela' =>
                'fornecedores',

                'mensagem' =>
                "Fornecedor inválido no item {$numero}."
            ]
        ];

        foreach (
            $validacoes as $validacao
        ) {
            if (empty($validacao['id'])) {
                continue;
            }

            $sql = "
                SELECT COUNT(*)

                FROM {$validacao['tabela']}

                WHERE id = :id
                  AND empresa_id =
                      :empresa_id
            ";

            $stmt =
                $this->pdo->prepare($sql);

            $stmt->execute([
                ':id' =>
                $validacao['id'],

                ':empresa_id' =>
                $empresaId
            ]);

            if (
                (int)$stmt->fetchColumn()
                !== 1
            ) {
                throw new RuntimeException(
                    $validacao['mensagem']
                );
            }
        }
    }

    private function calcularTotais(
        array $itens
    ): array {

        $debito = 0.0;
        $credito = 0.0;

        foreach ($itens as $item) {
            $item =
                $this->normalizarItem(
                    $item
                );

            $debito +=
                $item['debito'];

            $credito +=
                $item['credito'];
        }

        return [
            'debito' =>
            round($debito, 2),

            'credito' =>
            round($credito, 2)
        ];
    }

    private function validarBalanceamento(
        array $totais
    ): void {

        if (
            $totais['debito'] <= 0
            || $totais['credito'] <= 0
        ) {
            throw new RuntimeException(
                'O lançamento deve possuir valores de débito e crédito.'
            );
        }

        if (
            abs(
                $totais['debito']
                    - $totais['credito']
            ) > 0.009
        ) {
            throw new RuntimeException(
                'O total de débito deve ser igual ao total de crédito.'
            );
        }
    }

    // ==================================================
    // NUMERAÇÃO
    // ==================================================

    private function reservarNumeroDiario(
        int $diarioId,
        int $empresaId,
        int $exercicioId
    ): int {

        $stmt = $this->pdo->prepare("
            SELECT
                numeracao_automatica,
                ultimo_numero

            FROM diarios_contabilisticos

            WHERE id = :id
              AND empresa_id =
                  :empresa_id

            FOR UPDATE
        ");

        $stmt->execute([
            ':id' =>
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
                'Diário contabilístico não encontrado.'
            );
        }

        if (
            (int)$diario['numeracao_automatica'] !== 1
        ) {
            throw new RuntimeException(
                'O diário selecionado não utiliza numeração automática.'
            );
        }

        $numero =
            (int)$diario['ultimo_numero']
            + 1;

        $stmt = $this->pdo->prepare("
            UPDATE diarios_contabilisticos

            SET ultimo_numero =
                :numero

            WHERE id =
                :id

              AND empresa_id =
                :empresa_id
        ");

        $stmt->execute([
            ':numero' =>
            $numero,

            ':id' =>
            $diarioId,

            ':empresa_id' =>
            $empresaId
        ]);

        return $numero;
    }

    // ==================================================
    // STATUS
    // ==================================================

    private function statusId(
        string $tipo,
        string $nome
    ): int {

        $stmt = $this->pdo->prepare("
            SELECT id

            FROM status

            WHERE tipo =
                :tipo

              AND LOWER(nome) =
                LOWER(:nome)

            LIMIT 1
        ");

        $stmt->execute([
            ':tipo' =>
            $tipo,

            ':nome' =>
            $nome
        ]);

        $id =
            (int)$stmt->fetchColumn();

        if ($id <= 0) {
            throw new RuntimeException(
                "Status '{$nome}' do tipo '{$tipo}' não encontrado."
            );
        }

        return $id;
    }

    private function statusEh(
        int $statusId,
        string $tipo,
        string $nome
    ): bool {

        $stmt = $this->pdo->prepare("
            SELECT COUNT(*)

            FROM status

            WHERE id = :id
              AND tipo = :tipo
              AND LOWER(nome) =
                  LOWER(:nome)
        ");

        $stmt->execute([
            ':id' =>
            $statusId,

            ':tipo' =>
            $tipo,

            ':nome' =>
            $nome
        ]);

        return (
            (int)$stmt->fetchColumn()
            === 1
        );
    }

    // ==================================================
    // AUXILIARES
    // ==================================================

    private function dadosAuditoriaLancamento(
        array $lancamento
    ): array {

        return [
            'id' =>
            $lancamento['id'] ?? null,

            'empresa_id' =>
            $lancamento['empresa_id'] ?? null,

            'filial_id' =>
            $lancamento['filial_id'] ?? null,

            'exercicio_id' =>
            $lancamento['exercicio_id'] ?? null,

            'periodo_id' =>
            $lancamento['periodo_id'] ?? null,

            'diario_id' =>
            $lancamento['diario_id'] ?? null,

            'numero' =>
            $lancamento['numero'] ?? null,

            'numero_documento' =>
            $lancamento['numero_documento'] ?? null,

            'referencia' =>
            $lancamento['referencia'] ?? null,

            'data_lancamento' =>
            $lancamento['data_lancamento'] ?? null,

            'data_documento' =>
            $lancamento['data_documento'] ?? null,

            'descricao' =>
            $lancamento['descricao'] ?? null,

            'origem' =>
            $lancamento['origem'] ?? null,

            'origem_id' =>
            $lancamento['origem_id'] ?? null,

            'lancamento_estornado_id' =>
            $lancamento['lancamento_estornado_id'] ?? null,

            'status_id' =>
            $lancamento['status_id'] ?? null,

            'total_debito' =>
            $lancamento['total_debito'] ?? null,

            'total_credito' =>
            $lancamento['total_credito'] ?? null,

            'contabilizado_em' =>
            $lancamento['contabilizado_em'] ?? null,

            'contabilizado_por' =>
            $lancamento['contabilizado_por'] ?? null,

            'anulado_em' =>
            $lancamento['anulado_em'] ?? null,

            'anulado_por' =>
            $lancamento['anulado_por'] ?? null,

            'motivo_anulacao' =>
            $lancamento['motivo_anulacao'] ?? null,

            'criado_por' =>
            $lancamento['criado_por'] ?? null,

            'atualizado_por' =>
            $lancamento['atualizado_por'] ?? null,

            'itens' =>
            array_map(
                static function (array $item): array {

                    return [
                        'id' =>
                        $item['id'] ?? null,

                        'conta_contabil_id' =>
                        $item['conta_contabil_id'] ?? null,

                        'centro_custo_id' =>
                        $item['centro_custo_id'] ?? null,

                        'cliente_id' =>
                        $item['cliente_id'] ?? null,

                        'fornecedor_id' =>
                        $item['fornecedor_id'] ?? null,

                        'descricao' =>
                        $item['descricao'] ?? null,

                        'debito' =>
                        $item['debito'] ?? null,

                        'credito' =>
                        $item['credito'] ?? null,

                        'ordem' =>
                        $item['ordem'] ?? null
                    ];
                },
                $lancamento['itens'] ?? []
            )
        ];
    }

    private function validarEmpresa(
        int $empresaId
    ): void {

        if ($empresaId <= 0) {
            throw new RuntimeException(
                'Empresa inválida.'
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

    private function dataOuNull(
        mixed $valor
    ): ?string {

        $texto =
            trim(
                (string)(
                    $valor ?? ''
                )
            );

        if ($texto === '') {
            return null;
        }

        $this->validarData(
            $texto,
            'Data do documento'
        );

        return $texto;
    }

    private function textoOuNull(
        mixed $valor
    ): ?string {

        $texto =
            trim(
                (string)(
                    $valor ?? ''
                )
            );

        return $texto !== ''
            ? $texto
            : null;
    }

    private function decimal(
        mixed $valor
    ): float {

        if (
            is_string($valor)
        ) {
            $valor =
                str_replace(
                    [' ', ','],
                    ['', '.'],
                    $valor
                );
        }

        return round(
            max(
                0,
                (float)$valor
            ),
            2
        );
    }
}
