<?php

declare(strict_types=1);

require_once __DIR__ . '/../services/AuditoriaService.php';
require_once __DIR__ . '/../services/LogService.php';

final class Banco
{
    private PDO $pdo;
    private AuditoriaService $auditoria;
    private LogService $log;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;

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
        ?int $filialId = null
    ): array {

        $sql = "
            SELECT
                b.*,
                f.nome AS filial,
                s.nome AS status
            FROM bancos b

            LEFT JOIN filiais f
                ON f.id = b.filial_id

            INNER JOIN status s
                ON s.id = b.status_id

            WHERE b.empresa_id = :empresa_id
        ";

        $params = [
            ':empresa_id' => $empresaId
        ];

        if ($filialId !== null) {
            $sql .= "
                AND b.filial_id = :filial_id
            ";

            $params[':filial_id'] = $filialId;
        }

        $sql .= "
            ORDER BY b.id DESC
        ";

        $stmt = $this->pdo->prepare($sql);

        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ==================================================
    // BUSCAR
    // ==================================================

    public function buscar(
        int $id,
        int $empresaId
    ): ?array {

        $stmt = $this->pdo->prepare("
            SELECT
                b.*,
                f.nome AS filial,
                s.nome AS status
            FROM bancos b

            LEFT JOIN filiais f
                ON f.id = b.filial_id

            INNER JOIN status s
                ON s.id = b.status_id

            WHERE b.id = :id
              AND b.empresa_id = :empresa_id

            LIMIT 1
        ");

        $stmt->execute([
            ':id' => $id,
            ':empresa_id' => $empresaId
        ]);

        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    // ==================================================
    // FILIAIS
    // ==================================================

    public function listarFiliais(
        int $empresaId
    ): array {

        if ($empresaId <= 0) {
            throw new RuntimeException(
                'Empresa inválida.'
            );
        }

        $stmt =
            $this->pdo->prepare("
            SELECT
                f.id,
                f.nome,
                f.codigo,
                f.status_id,
                s.nome AS status_nome

            FROM filiais f

            INNER JOIN status s
                ON s.id = f.status_id
               AND s.tipo = 'filial'

            WHERE f.empresa_id = :empresa_id

              AND LOWER(TRIM(s.nome)) IN (
                    'activa',
                    'ativa'
              )

            ORDER BY
                f.nome ASC
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
    // CRIAR
    // ==================================================

    public function criar(array $dados): bool
    {
        $empresaId =
            (int)($dados['empresa_id'] ?? 0);

        $usuarioId =
            (int)($dados['usuario_id'] ?? 0);

        $statusId =
            (int)($dados['status_id'] ?? 0);

        $saldoInicial =
            round(
                (float)($dados['saldo_inicial'] ?? 0),
                2
            );


        $this->validarDados(
            $dados,
            $empresaId,
            $usuarioId,
            $statusId,
            $saldoInicial
        );


        $this->validarStatusBanco(
            $statusId
        );


        $this->pdo->beginTransaction();

        try {

            $stmt = $this->pdo->prepare("
            INSERT INTO bancos
            (
                empresa_id,
                filial_id,
                status_id,
                nome_banco,
                codigo_banco,
                agencia,
                digito_agencia,
                conta,
                digito_conta,
                iban,
                swift,
                titular,
                documento_titular,
                tipo_conta,
                moeda,
                saldo_inicial,
                saldo_atual,
                observacoes,
                criado_por
            )
            VALUES
            (
                :empresa_id,
                :filial_id,
                :status_id,
                :nome_banco,
                :codigo_banco,
                :agencia,
                :digito_agencia,
                :conta,
                :digito_conta,
                :iban,
                :swift,
                :titular,
                :documento_titular,
                :tipo_conta,
                :moeda,
                :saldo_inicial,
                :saldo_atual,
                :observacoes,
                :criado_por
            )
        ");


            $stmt->execute([
                ':empresa_id' =>
                $empresaId,

                ':filial_id' =>
                !empty($dados['filial_id'])
                    ? (int)$dados['filial_id']
                    : null,

                ':status_id' =>
                $statusId,

                ':nome_banco' =>
                trim((string)$dados['nome_banco']),

                ':codigo_banco' =>
                $this->textoOuNull(
                    $dados['codigo_banco'] ?? null
                ),

                ':agencia' =>
                $this->textoOuNull(
                    $dados['agencia'] ?? null
                ),

                ':digito_agencia' =>
                $this->textoOuNull(
                    $dados['digito_agencia'] ?? null
                ),

                ':conta' =>
                trim((string)$dados['conta']),

                ':digito_conta' =>
                $this->textoOuNull(
                    $dados['digito_conta'] ?? null
                ),

                ':iban' =>
                $this->textoOuNull(
                    $dados['iban'] ?? null
                ),

                ':swift' =>
                $this->textoOuNull(
                    $dados['swift'] ?? null
                ),

                ':titular' =>
                trim((string)$dados['titular']),

                ':documento_titular' =>
                $this->textoOuNull(
                    $dados['documento_titular'] ?? null
                ),

                ':tipo_conta' =>
                $dados['tipo_conta'],

                ':moeda' =>
                strtoupper(
                    trim((string)$dados['moeda'])
                ),

                ':saldo_inicial' =>
                $saldoInicial,

                ':saldo_atual' =>
                $saldoInicial,

                ':observacoes' =>
                $this->textoOuNull(
                    $dados['observacoes'] ?? null
                ),

                ':criado_por' =>
                $usuarioId
            ]);


            $id =
                (int)$this->pdo
                    ->lastInsertId();


            if ($id <= 0) {
                throw new RuntimeException(
                    'Não foi possível identificar o banco criado.'
                );
            }


            $banco =
                $this->buscarBancoAuditoria(
                    $id,
                    $empresaId
                );


            if (!$banco) {
                throw new RuntimeException(
                    'Não foi possível carregar o banco para auditoria.'
                );
            }


            $this->auditoria->insercao(
                $empresaId,
                $usuarioId,
                'bancos',
                $id,
                $this->dadosAuditoriaBanco($banco)
            );


            $this->log->registrar(
                $empresaId,
                $usuarioId,
                'BANCOS',
                'CRIAR',
                'Conta bancária criada: '
                    . $banco['nome_banco']
                    . ' - '
                    . $banco['conta'],
                $id,
                !empty($banco['filial_id'])
                    ? (int)$banco['filial_id']
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

    public function listarStatus(): array
    {
        $stmt = $this->pdo->prepare("
        SELECT
            id,
            tipo,
            nome,
            descricao
        FROM status
        WHERE tipo = 'banco'
        ORDER BY nome ASC
    ");

        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ==================================================
    // ATUALIZAR
    // ==================================================

    public function atualizar(array $dados): bool
    {
        $id =
            (int)($dados['id'] ?? 0);

        $empresaId =
            (int)($dados['empresa_id'] ?? 0);

        $usuarioId =
            (int)($dados['usuario_id'] ?? 0);

        $statusId =
            (int)($dados['status_id'] ?? 0);


        if ($id <= 0) {
            throw new RuntimeException(
                'Banco inválido.'
            );
        }


        $this->validarDados(
            $dados,
            $empresaId,
            $usuarioId,
            $statusId,
            0
        );


        $this->validarStatusBanco(
            $statusId
        );


        $this->pdo->beginTransaction();

        try {

            $stmt = $this->pdo->prepare("
            SELECT *
            FROM bancos
            WHERE id = :id
              AND empresa_id = :empresa_id
            FOR UPDATE
        ");

            $stmt->execute([
                ':id' =>
                $id,

                ':empresa_id' =>
                $empresaId
            ]);


            $bancoAntes =
                $stmt->fetch(
                    PDO::FETCH_ASSOC
                );


            if (!$bancoAntes) {
                throw new RuntimeException(
                    'Banco não encontrado.'
                );
            }


            $stmt = $this->pdo->prepare("
            UPDATE bancos
            SET
                filial_id = :filial_id,
                status_id = :status_id,
                nome_banco = :nome_banco,
                codigo_banco = :codigo_banco,
                agencia = :agencia,
                digito_agencia = :digito_agencia,
                conta = :conta,
                digito_conta = :digito_conta,
                iban = :iban,
                swift = :swift,
                titular = :titular,
                documento_titular = :documento_titular,
                tipo_conta = :tipo_conta,
                moeda = :moeda,
                observacoes = :observacoes,
                atualizado_por = :atualizado_por
            WHERE id = :id
              AND empresa_id = :empresa_id
        ");


            $stmt->execute([
                ':filial_id' =>
                !empty($dados['filial_id'])
                    ? (int)$dados['filial_id']
                    : null,

                ':status_id' =>
                $statusId,

                ':nome_banco' =>
                trim((string)$dados['nome_banco']),

                ':codigo_banco' =>
                $this->textoOuNull(
                    $dados['codigo_banco'] ?? null
                ),

                ':agencia' =>
                $this->textoOuNull(
                    $dados['agencia'] ?? null
                ),

                ':digito_agencia' =>
                $this->textoOuNull(
                    $dados['digito_agencia'] ?? null
                ),

                ':conta' =>
                trim((string)$dados['conta']),

                ':digito_conta' =>
                $this->textoOuNull(
                    $dados['digito_conta'] ?? null
                ),

                ':iban' =>
                $this->textoOuNull(
                    $dados['iban'] ?? null
                ),

                ':swift' =>
                $this->textoOuNull(
                    $dados['swift'] ?? null
                ),

                ':titular' =>
                trim((string)$dados['titular']),

                ':documento_titular' =>
                $this->textoOuNull(
                    $dados['documento_titular'] ?? null
                ),

                ':tipo_conta' =>
                $dados['tipo_conta'],

                ':moeda' =>
                strtoupper(
                    trim((string)$dados['moeda'])
                ),

                ':observacoes' =>
                $this->textoOuNull(
                    $dados['observacoes'] ?? null
                ),

                ':atualizado_por' =>
                $usuarioId,

                ':id' =>
                $id,

                ':empresa_id' =>
                $empresaId
            ]);


            $bancoDepois =
                $this->buscarBancoAuditoria(
                    $id,
                    $empresaId
                );


            if (!$bancoDepois) {
                throw new RuntimeException(
                    'Não foi possível carregar o banco atualizado.'
                );
            }


            $this->auditoria->atualizacao(
                $empresaId,
                $usuarioId,
                'bancos',
                $id,
                $this->dadosAuditoriaBanco(
                    $bancoAntes
                ),
                $this->dadosAuditoriaBanco(
                    $bancoDepois
                )
            );


            $this->log->registrar(
                $empresaId,
                $usuarioId,
                'BANCOS',
                'EDITAR',
                'Conta bancária atualizada: '
                    . $bancoDepois['nome_banco']
                    . ' - '
                    . $bancoDepois['conta'],
                $id,
                !empty($bancoDepois['filial_id'])
                    ? (int)$bancoDepois['filial_id']
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
    // ELIMINAR
    // ==================================================

    public function eliminar(
        int $id,
        int $empresaId,
        int $usuarioId
    ): bool {

        if ($id <= 0 || $empresaId <= 0) {
            throw new RuntimeException(
                'Banco inválido.'
            );
        }

        if ($usuarioId <= 0) {
            throw new RuntimeException(
                'Utilizador inválido.'
            );
        }


        $this->pdo->beginTransaction();

        try {

            /*
         * Bloquear banco.
         */
            $stmt = $this->pdo->prepare("
            SELECT *
            FROM bancos
            WHERE id = :id
              AND empresa_id = :empresa_id
            FOR UPDATE
        ");

            $stmt->execute([
                ':id' =>
                $id,

                ':empresa_id' =>
                $empresaId
            ]);


            $banco =
                $stmt->fetch(
                    PDO::FETCH_ASSOC
                );


            if (!$banco) {
                throw new RuntimeException(
                    'Banco não encontrado.'
                );
            }


            /*
         * Não permitir eliminar banco que já possui
         * histórico financeiro.
         */
            $stmt = $this->pdo->prepare("
            SELECT COUNT(*)
            FROM movimentos_bancarios
            WHERE banco_id = :banco_id
              AND empresa_id = :empresa_id
        ");

            $stmt->execute([
                ':banco_id' =>
                $id,

                ':empresa_id' =>
                $empresaId
            ]);


            if ((int)$stmt->fetchColumn() > 0) {
                throw new RuntimeException(
                    'Não é possível eliminar este banco porque existem movimentos bancários associados.'
                );
            }


            $dadosAnteriores =
                $this->dadosAuditoriaBanco(
                    $banco
                );


            $stmt = $this->pdo->prepare("
            DELETE FROM bancos
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
                    'Não foi possível eliminar o banco.'
                );
            }


            $this->auditoria->exclusao(
                $empresaId,
                $usuarioId,
                'bancos',
                $id,
                $dadosAnteriores
            );


            $this->log->registrar(
                $empresaId,
                $usuarioId,
                'BANCOS',
                'ELIMINAR',
                'Conta bancária eliminada: '
                    . $banco['nome_banco']
                    . ' - '
                    . $banco['conta'],
                $id,
                !empty($banco['filial_id'])
                    ? (int)$banco['filial_id']
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
    // SALDO ATUAL
    // ==================================================

    public function saldoAtual(
        int $id,
        int $empresaId
    ): float {

        $stmt = $this->pdo->prepare("
            SELECT saldo_atual
            FROM bancos
            WHERE id = :id
              AND empresa_id = :empresa_id
            LIMIT 1
        ");

        $stmt->execute([
            ':id' => $id,
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
    // VALIDAÇÕES
    // ==================================================

    private function validarDados(
        array $dados,
        int $empresaId,
        int $usuarioId,
        int $statusId,
        float $saldoInicial
    ): void {

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

        if ($statusId <= 0) {
            throw new RuntimeException(
                'Selecione um status.'
            );
        }

        if (
            empty($dados['nome_banco'])
            || trim((string)$dados['nome_banco']) === ''
        ) {
            throw new RuntimeException(
                'Informe o nome do banco.'
            );
        }

        if (
            empty($dados['conta'])
            || trim((string)$dados['conta']) === ''
        ) {
            throw new RuntimeException(
                'Informe o número da conta.'
            );
        }

        if (
            empty($dados['titular'])
            || trim((string)$dados['titular']) === ''
        ) {
            throw new RuntimeException(
                'Informe o titular da conta.'
            );
        }

        $tiposPermitidos = [
            'Corrente',
            'Poupança',
            'Salário',
            'Empresarial'
        ];

        if (
            empty($dados['tipo_conta'])
            || !in_array(
                $dados['tipo_conta'],
                $tiposPermitidos,
                true
            )
        ) {
            throw new RuntimeException(
                'Tipo de conta inválido.'
            );
        }

        $moeda = strtoupper(
            trim((string)($dados['moeda'] ?? ''))
        );

        if (strlen($moeda) !== 3) {
            throw new RuntimeException(
                'A moeda deve possuir três caracteres.'
            );
        }

        if ($saldoInicial < 0) {
            throw new RuntimeException(
                'O saldo inicial não pode ser negativo.'
            );
        }
    }

    private function validarStatusBanco(
        int $statusId
    ): void {

        $stmt = $this->pdo->prepare("
            SELECT COUNT(*)
            FROM status
            WHERE id = :id
              AND tipo = 'banco'
        ");

        $stmt->execute([
            ':id' => $statusId
        ]);

        if ((int)$stmt->fetchColumn() !== 1) {
            throw new RuntimeException(
                'Status de banco inválido.'
            );
        }
    }

    private function buscarBancoAuditoria(
        int $id,
        int $empresaId
    ): ?array {

        $stmt = $this->pdo->prepare("
        SELECT *
        FROM bancos
        WHERE id = :id
          AND empresa_id = :empresa_id
        LIMIT 1
    ");

        $stmt->execute([
            ':id' =>
            $id,

            ':empresa_id' =>
            $empresaId
        ]);

        return $stmt->fetch(
            PDO::FETCH_ASSOC
        ) ?: null;
    }


    private function dadosAuditoriaBanco(
        array $banco
    ): array {

        return [
            'id' =>
            isset($banco['id'])
                ? (int)$banco['id']
                : null,

            'empresa_id' =>
            isset($banco['empresa_id'])
                ? (int)$banco['empresa_id']
                : null,

            'filial_id' =>
            !empty($banco['filial_id'])
                ? (int)$banco['filial_id']
                : null,

            'status_id' =>
            isset($banco['status_id'])
                ? (int)$banco['status_id']
                : null,

            'nome_banco' =>
            $banco['nome_banco'] ?? null,

            'codigo_banco' =>
            $banco['codigo_banco'] ?? null,

            'agencia' =>
            $banco['agencia'] ?? null,

            'digito_agencia' =>
            $banco['digito_agencia'] ?? null,

            'conta' =>
            $banco['conta'] ?? null,

            'digito_conta' =>
            $banco['digito_conta'] ?? null,

            'iban' =>
            $banco['iban'] ?? null,

            'swift' =>
            $banco['swift'] ?? null,

            'titular' =>
            $banco['titular'] ?? null,

            'documento_titular' =>
            $banco['documento_titular'] ?? null,

            'tipo_conta' =>
            $banco['tipo_conta'] ?? null,

            'moeda' =>
            $banco['moeda'] ?? null,

            'saldo_inicial' =>
            isset($banco['saldo_inicial'])
                ? (float)$banco['saldo_inicial']
                : 0.0,

            'saldo_atual' =>
            isset($banco['saldo_atual'])
                ? (float)$banco['saldo_atual']
                : 0.0,

            'observacoes' =>
            $banco['observacoes'] ?? null
        ];
    }

    private function textoOuNull(
        mixed $valor
    ): ?string {

        if ($valor === null) {
            return null;
        }

        $texto = trim((string)$valor);

        return $texto === ''
            ? null
            : $texto;
    }
}
