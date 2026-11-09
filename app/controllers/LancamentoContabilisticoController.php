<?php

declare(strict_types=1);

require_once __DIR__
    . '/../models/LancamentoContabilistico.php';

final class LancamentoContabilisticoController
{
    private PDO $pdo;

    private LancamentoContabilistico $lancamento;

    public function __construct(PDO $pdo)
    {
        $this->pdo =
            $pdo;

        $this->lancamento =
            new LancamentoContabilistico(
                $pdo
            );
    }

    // ==================================================
    // LISTAR
    // ==================================================

    public function listar(
        array $filtros = []
    ): array {

        return $this->lancamento->listar(
            $this->empresaId(),
            $this->filialId(),
            $filtros
        );
    }

    // ==================================================
    // BUSCAR
    // ==================================================

    public function buscar(
        int $id
    ): ?array {

        $this->validarId(
            $id,
            'Lançamento contabilístico inválido.'
        );

        $lancamento =
            $this->lancamento->buscar(
                $id,
                $this->empresaId()
            );

        if (
            $lancamento
            && !$this->podeAcederFilial(
                $lancamento['filial_id']
                    !== null
                    ? (int)$lancamento['filial_id']
                    : null
            )
        ) {
            throw new RuntimeException(
                'Este lançamento pertence a outra filial.'
            );
        }

        return $lancamento;
    }

    // ==================================================
    // GUARDAR
    // ==================================================

    public function guardar(
        array $dados
    ): int {

        $dados['empresa_id'] =
            $this->empresaId();

        $dados['usuario_id'] =
            $this->usuarioId();

        /*
         * Lançamentos criados manualmente usam sempre
         * a filial da sessão.
         *
         * Um utilizador de nível geral poderá ficar
         * sem filial, gerando um lançamento geral.
         */
        $dados['filial_id'] =
            $this->filialId();

        /*
         * O CRUD desta página cria apenas lançamentos
         * manuais. As integrações automáticas chamarão
         * futuramente um serviço próprio.
         */
        $dados['origem'] =
            'MANUAL';

        $dados['origem_id'] =
            null;

        unset(
            $dados['empresa_id_formulario'],
            $dados['usuario_id_formulario'],
            $dados['filial_id_formulario'],
            $dados['status_id'],
            $dados['numero'],
            $dados['total_debito'],
            $dados['total_credito'],
            $dados['contabilizado_em'],
            $dados['contabilizado_por'],
            $dados['anulado_em'],
            $dados['anulado_por'],
            $dados['motivo_anulacao']
        );

        return $this->lancamento
            ->guardar(
                $dados
            );
    }

    // ==================================================
    // CONTABILIZAR
    // ==================================================

    public function contabilizar(
        int $id
    ): bool {

        $this->validarAcessoLancamento(
            $id
        );

        return $this->lancamento
            ->contabilizar(
                $id,
                $this->empresaId(),
                $this->usuarioId()
            );
    }

    // ==================================================
    // ANULAR
    // ==================================================

    public function anular(
        int $id,
        string $motivo,
        string $dataEstorno
    ): int {

        $this->validarAcessoLancamento(
            $id
        );

        $motivo =
            trim($motivo);

        if ($motivo === '') {
            throw new RuntimeException(
                'Informe o motivo da anulação.'
            );
        }

        $dataEstorno =
            trim($dataEstorno);

        if ($dataEstorno === '') {
            throw new RuntimeException(
                'Informe a data do estorno.'
            );
        }

        return $this->lancamento
            ->estornar(
                $id,
                $this->empresaId(),
                $this->usuarioId(),
                $motivo,
                $dataEstorno
            );
    }

    // ==================================================
    // ELIMINAR
    // ==================================================

    public function eliminar(
        int $id
    ): bool {

        $this->validarAcessoLancamento(
            $id
        );

        return $this->lancamento
            ->eliminar(
                $id,
                $this->empresaId(),
                $this->usuarioId()
            );
    }

    // ==================================================
    // EXERCÍCIOS
    // ==================================================

    public function exercicios(): array
    {
        return $this->lancamento
            ->listarExercicios(
                $this->empresaId()
            );
    }

    // ==================================================
    // PERÍODOS
    // ==================================================

    public function periodos(
        int $exercicioId
    ): array {

        $this->validarId(
            $exercicioId,
            'Exercício contabilístico inválido.'
        );

        return $this->lancamento
            ->listarPeriodos(
                $this->empresaId(),
                $exercicioId
            );
    }

    // ==================================================
    // DIÁRIOS
    // ==================================================

    public function diarios(): array
    {
        $diarios =
            $this->lancamento
            ->listarDiarios(
                $this->empresaId()
            );

        /*
         * Na página de lançamentos manuais,
         * só mostramos diários que permitem
         * lançamentos manuais.
         */
        return array_values(
            array_filter(
                $diarios,
                static function (
                    array $diario
                ): bool {

                    return (int)(
                        $diario['permite_manual']
                        ?? 0
                    ) === 1;
                }
            )
        );
    }

    // ==================================================
    // CONTAS
    // ==================================================

    public function contas(): array
    {
        return $this->lancamento
            ->listarContas(
                $this->empresaId()
            );
    }

    // ==================================================
    // STATUS
    // ==================================================

    public function status(): array
    {
        return $this->lancamento
            ->listarStatus();
    }

    // ==================================================
    // FILIAIS
    // ==================================================

    public function filiais(): array
    {
        $filialSessao =
            $this->filialId();

        if ($filialSessao !== null) {
            $stmt =
                $this->pdo
                ->prepare("
                        SELECT
                            id,
                            nome

                        FROM filiais

                        WHERE id = :id
                          AND empresa_id = :empresa_id

                        LIMIT 1
                    ");

            $stmt->execute([
                ':id' =>
                $filialSessao,

                ':empresa_id' =>
                $this->empresaId()
            ]);

            $filial =
                $stmt->fetch(
                    PDO::FETCH_ASSOC
                );

            return $filial
                ? [$filial]
                : [];
        }

        $stmt =
            $this->pdo
            ->prepare("
                    SELECT
                        id,
                        nome

                    FROM filiais

                    WHERE empresa_id =
                        :empresa_id

                    ORDER BY nome ASC
                ");

        $stmt->execute([
            ':empresa_id' =>
            $this->empresaId()
        ]);

        return $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );
    }

    // ==================================================
    // CENTROS DE CUSTO
    // ==================================================

    public function centrosCusto(): array
    {
        $sql = "
            SELECT
                centro.id,
                centro.codigo,
                centro.nome

            FROM centros_custo centro

            INNER JOIN status st
                ON st.id =
                    centro.status_id

               AND st.tipo =
                    'centro_custo'

            WHERE centro.empresa_id =
                :empresa_id

              AND LOWER(st.nome) =
                    'activo'
        ";

        $params = [
            ':empresa_id' =>
            $this->empresaId()
        ];

        $filialId =
            $this->filialId();

        if ($filialId !== null) {
            $sql .= "
                AND (
                    centro.filial_id =
                        :filial_id

                    OR centro.filial_id
                        IS NULL
                )
            ";

            $params[':filial_id'] =
                $filialId;
        }

        $sql .= "
            ORDER BY
                centro.codigo ASC,
                centro.nome ASC
        ";

        $stmt = $this->pdo
            ->prepare($sql);

        $stmt->execute($params);

        return $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );
    }

    // ==================================================
    // CLIENTES
    // ==================================================

    public function clientes(): array
    {
        $stmt =
            $this->pdo
            ->prepare("
                    SELECT
                        id,
                        nome

                    FROM clientes

                    WHERE empresa_id =
                        :empresa_id

                    ORDER BY nome ASC
                ");

        $stmt->execute([
            ':empresa_id' =>
            $this->empresaId()
        ]);

        return $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );
    }

    // ==================================================
    // FORNECEDORES
    // ==================================================

    public function fornecedores(): array
    {
        $stmt =
            $this->pdo
            ->prepare("
                    SELECT
                        id,
                        nome

                    FROM fornecedores

                    WHERE empresa_id =
                        :empresa_id

                    ORDER BY nome ASC
                ");

        $stmt->execute([
            ':empresa_id' =>
            $this->empresaId()
        ]);

        return $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );
    }

    // ==================================================
    // VALIDAR ACESSO AO LANÇAMENTO
    // ==================================================

    private function validarAcessoLancamento(
        int $id
    ): array {

        $this->validarId(
            $id,
            'Lançamento contabilístico inválido.'
        );

        $lancamento =
            $this->lancamento->buscar(
                $id,
                $this->empresaId()
            );

        if (!$lancamento) {
            throw new RuntimeException(
                'Lançamento contabilístico não encontrado.'
            );
        }

        $filialLancamento =
            $lancamento['filial_id']
            !== null
            ? (int)$lancamento['filial_id']
            : null;

        if (
            !$this->podeAcederFilial(
                $filialLancamento
            )
        ) {
            throw new RuntimeException(
                'Este lançamento pertence a outra filial.'
            );
        }

        return $lancamento;
    }

    private function podeAcederFilial(
        ?int $filialLancamento
    ): bool {

        $filialSessao =
            $this->filialId();

        if ($filialSessao === null) {
            return true;
        }

        return (
            $filialLancamento === null
            || $filialLancamento
            === $filialSessao
        );
    }

    // ==================================================
    // VALIDAÇÕES
    // ==================================================

    private function validarId(
        int $id,
        string $mensagem
    ): void {

        if ($id <= 0) {
            throw new RuntimeException(
                $mensagem
            );
        }
    }

    // ==================================================
    // SESSÃO
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

    private function usuarioId(): int
    {
        $usuarioId =
            Auth::usuarioId();

        if (
            $usuarioId === null
            || $usuarioId <= 0
        ) {
            throw new RuntimeException(
                'Utilizador não autenticado.'
            );
        }

        return (int)$usuarioId;
    }

    private function filialId(): ?int
    {
        $filialId =
            Auth::filialId();

        return (
            $filialId !== null
            && $filialId > 0
        )
            ? (int)$filialId
            : null;
    }
}
