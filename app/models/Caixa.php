<?php

declare(strict_types=1);

require_once __DIR__ . '/../services/AuditoriaService.php';
require_once __DIR__ . '/../services/LogService.php';

final class Caixa
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
    // LISTAR CAIXAS DA EMPRESA DO USUÁRIO
    // ==================================================

    public function listar(
        int $empresa_id,
        ?int $filial_id = null
    ): array {


        $sql = "

            SELECT

                c.*,

                f.nome AS filial,

                s.nome AS status


            FROM caixas c


            LEFT JOIN filiais f
                ON f.id = c.filial_id


            LEFT JOIN status s
                ON s.id = c.status_id


            WHERE c.empresa_id = :empresa_id

        ";

        $params = [

            ':empresa_id' => $empresa_id

        ];

        if ($filial_id) {

            $sql .= "AND c.filial_id = :filial_id";

            $params[':filial_id'] = $filial_id;
        }

        $sql .= "

            ORDER BY c.id DESC

        ";

        $stmt = $this->pdo->prepare($sql);

        $stmt->execute($params);


        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }



    public function fechar(array $dados): array
    {
        $empresaId =
            (int)($dados['empresa_id'] ?? 0);

        $caixaId =
            (int)($dados['caixa_id'] ?? 0);

        $usuarioId =
            (int)($dados['usuario_id'] ?? 0);

        $saldoInformado =
            round(
                (float)($dados['saldo_informado'] ?? 0),
                2
            );

        $observacoes =
            !empty($dados['observacoes'])
            ? trim(
                (string)$dados['observacoes']
            )
            : null;


        if ($empresaId <= 0) {
            throw new RuntimeException(
                'Empresa inválida.'
            );
        }

        if ($caixaId <= 0) {
            throw new RuntimeException(
                'Caixa inválido.'
            );
        }

        if ($usuarioId <= 0) {
            throw new RuntimeException(
                'Utilizador inválido.'
            );
        }

        if ($saldoInformado < 0) {
            throw new RuntimeException(
                'O saldo informado não pode ser negativo.'
            );
        }


        $this->pdo->beginTransaction();

        try {

            /*
         * Bloquear caixa.
         */
            $stmt =
                $this->pdo->prepare("
                SELECT *

                FROM caixas

                WHERE id = :caixa_id
                  AND empresa_id = :empresa_id

                FOR UPDATE
            ");


            $stmt->execute([
                ':caixa_id' =>
                $caixaId,

                ':empresa_id' =>
                $empresaId
            ]);


            $caixaAntes =
                $stmt->fetch(
                    PDO::FETCH_ASSOC
                );


            if (!$caixaAntes) {
                throw new RuntimeException(
                    'Caixa não encontrado.'
                );
            }


            /*
         * Bloquear abertura ativa.
         */
            $stmt =
                $this->pdo->prepare("
                SELECT *

                FROM abertura_caixa

                WHERE caixa_id = :caixa_id
                  AND empresa_id = :empresa_id
                  AND data_fechamento IS NULL

                ORDER BY id DESC

                LIMIT 1

                FOR UPDATE
            ");


            $stmt->execute([
                ':caixa_id' =>
                $caixaId,

                ':empresa_id' =>
                $empresaId
            ]);


            $aberturaAntes =
                $stmt->fetch(
                    PDO::FETCH_ASSOC
                );


            if (!$aberturaAntes) {
                throw new RuntimeException(
                    'Este caixa já está fechado ou não possui uma abertura ativa.'
                );
            }


            /*
         * Como o caixa já está bloqueado,
         * podemos usar diretamente o saldo_atual.
         */
            $saldoSistema =
                round(
                    (float)$caixaAntes['saldo_atual'],
                    2
                );


            $diferenca =
                round(
                    $saldoInformado
                        - $saldoSistema,
                    2
                );


            $statusFechado =
                $this->statusFechado();


            $stmt =
                $this->pdo->prepare("
                UPDATE abertura_caixa

                SET
                    data_fechamento = NOW(),
                    saldo_sistema = :saldo_sistema,
                    saldo_informado = :saldo_informado,
                    diferenca = :diferenca,
                    observacoes = :observacoes,
                    status_id = :status_id

                WHERE id = :abertura_id
                  AND caixa_id = :caixa_id
                  AND empresa_id = :empresa_id
            ");


            $stmt->execute([
                ':saldo_sistema' =>
                $saldoSistema,

                ':saldo_informado' =>
                $saldoInformado,

                ':diferenca' =>
                $diferenca,

                ':observacoes' =>
                $observacoes,

                ':status_id' =>
                $statusFechado,

                ':abertura_id' =>
                (int)$aberturaAntes['id'],

                ':caixa_id' =>
                $caixaId,

                ':empresa_id' =>
                $empresaId
            ]);


            if ($stmt->rowCount() !== 1) {
                throw new RuntimeException(
                    'Não foi possível encerrar a abertura do caixa.'
                );
            }


            $stmt =
                $this->pdo->prepare("
                UPDATE caixas

                SET status_id = :status_id

                WHERE id = :caixa_id
                  AND empresa_id = :empresa_id
            ");


            $stmt->execute([
                ':status_id' =>
                $statusFechado,

                ':caixa_id' =>
                $caixaId,

                ':empresa_id' =>
                $empresaId
            ]);


            if ($stmt->rowCount() !== 1) {
                throw new RuntimeException(
                    'Não foi possível atualizar o estado do caixa.'
                );
            }


            $aberturaDepois =
                $this->buscarAberturaAuditoria(
                    (int)$aberturaAntes['id'],
                    $empresaId
                );

            $caixaDepois =
                $this->buscarCaixaAuditoria(
                    $caixaId,
                    $empresaId
                );


            if (
                !$aberturaDepois
                || !$caixaDepois
            ) {
                throw new RuntimeException(
                    'Não foi possível carregar o fechamento para auditoria.'
                );
            }


            $this->auditoria->atualizacao(
                $empresaId,
                $usuarioId,
                'abertura_caixa',
                (int)$aberturaAntes['id'],
                $aberturaAntes,
                $aberturaDepois
            );


            $this->auditoria->atualizacao(
                $empresaId,
                $usuarioId,
                'caixas',
                $caixaId,
                $this->dadosAuditoriaCaixa(
                    $caixaAntes
                ),
                $this->dadosAuditoriaCaixa(
                    $caixaDepois
                )
            );


            $this->log->registrar(
                $empresaId,
                $usuarioId,
                'CAIXAS',
                'FECHAR',
                'Caixa fechado: '
                    . $caixaDepois['nome']
                    . '. Saldo sistema: '
                    . number_format(
                        $saldoSistema,
                        2,
                        ',',
                        '.'
                    )
                    . ' MZN; diferença: '
                    . number_format(
                        $diferenca,
                        2,
                        ',',
                        '.'
                    )
                    . ' MZN',
                $caixaId,
                !empty($caixaDepois['filial_id'])
                    ? (int)$caixaDepois['filial_id']
                    : null
            );


            $this->pdo->commit();


            return [
                'saldo_sistema' =>
                $saldoSistema,

                'saldo_informado' =>
                $saldoInformado,

                'diferenca' =>
                $diferenca
            ];
        } catch (Throwable $e) {

            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw $e;
        }
    }

    public function filiais(
        int $empresaId
    ): array {

        if ($empresaId <= 0) {
            throw new RuntimeException(
                'Empresa inválida.'
            );
        }

        $sql = "
        SELECT
            f.id,
            f.nome,
            f.codigo

        FROM filiais f

        WHERE f.empresa_id = :empresa_id

        ORDER BY
            f.nome ASC
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


    private function obterStatusId(string $nome): int
    {
        $stmt = $this->pdo->prepare("
        SELECT id
        FROM status
        WHERE tipo = 'caixa'
          AND LOWER(nome) = LOWER(:nome)
        LIMIT 1
    ");

        $stmt->execute([
            ':nome' => $nome
        ]);

        $id = $stmt->fetchColumn();

        if ($id === false) {
            throw new RuntimeException(
                'Status de caixa "' . $nome . '" não encontrado.'
            );
        }

        return (int)$id;
    }



    // ==================================================
    // BUSCAR POR ID
    // ==================================================

    public function buscar(
        int $id,
        int $empresa_id
    ): ?array {


        $stmt = $this->pdo->prepare("

            SELECT *

            FROM caixas

            WHERE id=:id

            AND empresa_id=:empresa_id

            LIMIT 1

        ");



        $stmt->execute([

            ':id' => $id,

            ':empresa_id' => $empresa_id

        ]);



        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }


    // ==================================================
    // CRIAR CAIXA
    // ==================================================

    public function criar(array $dados): bool
    {
        $empresaId =
            (int)($dados['empresa_id'] ?? 0);

        $usuarioId =
            (int)($dados['usuario_id'] ?? 0);

        $filialId =
            !empty($dados['filial_id'])
            ? (int)$dados['filial_id']
            : null;

        $nome =
            trim(
                (string)($dados['nome'] ?? '')
            );

        $codigo =
            !empty($dados['codigo'])
            ? trim((string)$dados['codigo'])
            : null;

        $descricao =
            !empty($dados['descricao'])
            ? trim((string)$dados['descricao'])
            : null;

        $localizacao =
            !empty($dados['localizacao'])
            ? trim((string)$dados['localizacao'])
            : null;

        $saldoInicial =
            round(
                (float)($dados['saldo_inicial'] ?? 0),
                2
            );


        if ($empresaId <= 0) {
            throw new RuntimeException(
                'Empresa inválida.'
            );
        }

        if ($usuarioId <= 0) {
            throw new RuntimeException(
                'Utilizador não autenticado.'
            );
        }

        if ($nome === '') {
            throw new RuntimeException(
                'Informe o nome do caixa.'
            );
        }

        if ($saldoInicial < 0) {
            throw new RuntimeException(
                'O saldo inicial não pode ser negativo.'
            );
        }


        $this->pdo->beginTransaction();

        try {

            $stmt =
                $this->pdo->prepare("
                INSERT INTO caixas
                (
                    empresa_id,
                    filial_id,
                    status_id,

                    nome,
                    codigo,
                    descricao,
                    localizacao,

                    saldo_inicial,
                    saldo_atual,

                    criado_por
                )
                VALUES
                (
                    :empresa_id,
                    :filial_id,
                    :status_id,

                    :nome,
                    :codigo,
                    :descricao,
                    :localizacao,

                    :saldo_inicial,
                    :saldo_atual,

                    :usuario_id
                )
            ");


            $stmt->execute([
                ':empresa_id' =>
                $empresaId,

                ':filial_id' =>
                $filialId,

                ':status_id' =>
                $this->statusFechado(),

                ':nome' =>
                $nome,

                ':codigo' =>
                $codigo,

                ':descricao' =>
                $descricao,

                ':localizacao' =>
                $localizacao,

                ':saldo_inicial' =>
                $saldoInicial,

                ':saldo_atual' =>
                $saldoInicial,

                ':usuario_id' =>
                $usuarioId
            ]);


            $caixaId =
                (int)$this->pdo
                    ->lastInsertId();


            if ($caixaId <= 0) {
                throw new RuntimeException(
                    'Não foi possível identificar o caixa criado.'
                );
            }


            $caixa =
                $this->buscarCaixaAuditoria(
                    $caixaId,
                    $empresaId
                );


            if (!$caixa) {
                throw new RuntimeException(
                    'Não foi possível carregar o caixa criado.'
                );
            }


            $this->auditoria->insercao(
                $empresaId,
                $usuarioId,
                'caixas',
                $caixaId,
                $this->dadosAuditoriaCaixa(
                    $caixa
                )
            );


            $this->log->registrar(
                $empresaId,
                $usuarioId,
                'CAIXAS',
                'CRIAR',
                'Caixa criado: '
                    . $caixa['nome'],
                $caixaId,
                $filialId
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

        $filialId =
            !empty($dados['filial_id'])
            ? (int)$dados['filial_id']
            : null;


        if ($id <= 0) {
            throw new RuntimeException(
                'Caixa inválido.'
            );
        }

        if ($empresaId <= 0) {
            throw new RuntimeException(
                'Empresa inválida.'
            );
        }

        if ($usuarioId <= 0) {
            throw new RuntimeException(
                'Utilizador não autenticado.'
            );
        }


        $this->pdo->beginTransaction();

        try {

            $stmt =
                $this->pdo->prepare("
                SELECT *

                FROM caixas

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


            $caixaAntes =
                $stmt->fetch(
                    PDO::FETCH_ASSOC
                );


            if (!$caixaAntes) {
                throw new RuntimeException(
                    'Caixa não encontrado.'
                );
            }


            if (
                (int)$caixaAntes['status_id']
                === $this->statusAberto()
            ) {
                throw new RuntimeException(
                    'Não é permitido editar um caixa aberto.'
                );
            }


            $stmt =
                $this->pdo->prepare("
                UPDATE caixas

                SET
                    filial_id =
                        :filial_id,

                    nome =
                        :nome,

                    codigo =
                        :codigo,

                    descricao =
                        :descricao,

                    localizacao =
                        :localizacao

                WHERE id = :id
                  AND empresa_id = :empresa_id
            ");


            $stmt->execute([
                ':filial_id' =>
                $filialId,

                ':nome' =>
                trim(
                    (string)($dados['nome'] ?? '')
                ),

                ':codigo' =>
                !empty($dados['codigo'])
                    ? trim(
                        (string)$dados['codigo']
                    )
                    : null,

                ':descricao' =>
                !empty($dados['descricao'])
                    ? trim(
                        (string)$dados['descricao']
                    )
                    : null,

                ':localizacao' =>
                !empty($dados['localizacao'])
                    ? trim(
                        (string)$dados['localizacao']
                    )
                    : null,

                ':id' =>
                $id,

                ':empresa_id' =>
                $empresaId
            ]);


            $caixaDepois =
                $this->buscarCaixaAuditoria(
                    $id,
                    $empresaId
                );


            if (!$caixaDepois) {
                throw new RuntimeException(
                    'Não foi possível carregar o caixa atualizado.'
                );
            }


            $this->auditoria->atualizacao(
                $empresaId,
                $usuarioId,
                'caixas',
                $id,
                $this->dadosAuditoriaCaixa(
                    $caixaAntes
                ),
                $this->dadosAuditoriaCaixa(
                    $caixaDepois
                )
            );


            $this->log->registrar(
                $empresaId,
                $usuarioId,
                'CAIXAS',
                'EDITAR',
                'Caixa atualizado: '
                    . $caixaDepois['nome'],
                $id,
                !empty($caixaDepois['filial_id'])
                    ? (int)$caixaDepois['filial_id']
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

        if ($id <= 0) {
            throw new RuntimeException(
                'Caixa inválido.'
            );
        }

        if ($empresaId <= 0) {
            throw new RuntimeException(
                'Empresa inválida.'
            );
        }

        if ($usuarioId <= 0) {
            throw new RuntimeException(
                'Utilizador não autenticado.'
            );
        }


        $this->pdo->beginTransaction();

        try {

            $stmt =
                $this->pdo->prepare("
                SELECT *

                FROM caixas

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


            $caixa =
                $stmt->fetch(
                    PDO::FETCH_ASSOC
                );


            if (!$caixa) {
                throw new RuntimeException(
                    'Caixa não encontrado.'
                );
            }


            /*
         * Movimentos.
         */
            $stmt =
                $this->pdo->prepare("
                SELECT COUNT(*)

                FROM movimentos_caixa

                WHERE caixa_id = :id
                  AND empresa_id = :empresa_id
            ");

            $stmt->execute([
                ':id' => $id,
                ':empresa_id' => $empresaId
            ]);


            if (
                (int)$stmt->fetchColumn()
                > 0
            ) {
                throw new RuntimeException(
                    'Não é possível eliminar um caixa com movimentos.'
                );
            }


            /*
         * Aberturas.
         */
            $stmt =
                $this->pdo->prepare("
                SELECT COUNT(*)

                FROM abertura_caixa

                WHERE caixa_id = :id
                  AND empresa_id = :empresa_id
            ");

            $stmt->execute([
                ':id' => $id,
                ':empresa_id' => $empresaId
            ]);


            if (
                (int)$stmt->fetchColumn()
                > 0
            ) {
                throw new RuntimeException(
                    'Não é possível eliminar um caixa que possui histórico de abertura.'
                );
            }


            /*
         * Transferências.
         */
            $stmt =
                $this->pdo->prepare("
                SELECT COUNT(*)

                FROM transferencias_caixa

                WHERE empresa_id = :empresa_id

                  AND (
                    caixa_origem_id = :id
                    OR caixa_destino_id = :id
                  )
            ");

            $stmt->execute([
                ':empresa_id' =>
                $empresaId,

                ':id' =>
                $id
            ]);


            if (
                (int)$stmt->fetchColumn()
                > 0
            ) {
                throw new RuntimeException(
                    'Não é possível eliminar um caixa com histórico de transferências.'
                );
            }


            $dadosAnteriores =
                $this->dadosAuditoriaCaixa(
                    $caixa
                );


            $stmt =
                $this->pdo->prepare("
                DELETE FROM caixas

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
                    'Não foi possível eliminar o caixa.'
                );
            }


            $this->auditoria->exclusao(
                $empresaId,
                $usuarioId,
                'caixas',
                $id,
                $dadosAnteriores
            );


            $this->log->registrar(
                $empresaId,
                $usuarioId,
                'CAIXAS',
                'ELIMINAR',
                'Caixa eliminado: '
                    . $caixa['nome'],
                $id,
                !empty($caixa['filial_id'])
                    ? (int)$caixa['filial_id']
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
    // ABRIR CAIXA
    // ==================================================

    public function abrir(array $dados): bool
    {
        $empresaId =
            (int)($dados['empresa_id'] ?? 0);

        $caixaId =
            (int)($dados['caixa_id'] ?? 0);

        $usuarioId =
            (int)($dados['usuario_id'] ?? 0);

        $valorInicial =
            round(
                (float)($dados['valor_inicial'] ?? 0),
                2
            );

        $observacoes =
            !empty($dados['observacoes'])
            ? trim(
                (string)$dados['observacoes']
            )
            : null;


        if ($empresaId <= 0) {
            throw new RuntimeException(
                'Empresa inválida.'
            );
        }

        if ($caixaId <= 0) {
            throw new RuntimeException(
                'Caixa inválido.'
            );
        }

        if ($usuarioId <= 0) {
            throw new RuntimeException(
                'Utilizador inválido.'
            );
        }

        if ($valorInicial < 0) {
            throw new RuntimeException(
                'O valor inicial não pode ser negativo.'
            );
        }


        $this->pdo->beginTransaction();

        try {

            /*
         * Bloquear o caixa.
         */
            $stmt =
                $this->pdo->prepare("
                SELECT *

                FROM caixas

                WHERE id = :caixa_id
                  AND empresa_id = :empresa_id

                FOR UPDATE
            ");


            $stmt->execute([
                ':caixa_id' =>
                $caixaId,

                ':empresa_id' =>
                $empresaId
            ]);


            $caixaAntes =
                $stmt->fetch(
                    PDO::FETCH_ASSOC
                );


            if (!$caixaAntes) {
                throw new RuntimeException(
                    'Caixa não encontrado.'
                );
            }


            /*
         * Verificar abertura ativa.
         */
            $stmt =
                $this->pdo->prepare("
                SELECT id

                FROM abertura_caixa

                WHERE caixa_id = :caixa_id
                  AND empresa_id = :empresa_id
                  AND data_fechamento IS NULL

                ORDER BY id DESC

                LIMIT 1

                FOR UPDATE
            ");


            $stmt->execute([
                ':caixa_id' =>
                $caixaId,

                ':empresa_id' =>
                $empresaId
            ]);


            if ($stmt->fetchColumn() !== false) {
                throw new RuntimeException(
                    'Este caixa já está aberto.'
                );
            }


            $statusAberto =
                $this->statusAberto();


            if ($statusAberto <= 0) {
                throw new RuntimeException(
                    'O status Aberto não está cadastrado.'
                );
            }


            $filialId =
                !empty($caixaAntes['filial_id'])
                ? (int)$caixaAntes['filial_id']
                : null;


            /*
         * Criar abertura.
         */
            $stmt =
                $this->pdo->prepare("
                INSERT INTO abertura_caixa
                (
                    empresa_id,
                    filial_id,
                    caixa_id,
                    usuario_id,
                    status_id,
                    data_abertura,
                    valor_inicial,
                    observacoes
                )
                VALUES
                (
                    :empresa_id,
                    :filial_id,
                    :caixa_id,
                    :usuario_id,
                    :status_id,
                    NOW(),
                    :valor_inicial,
                    :observacoes
                )
            ");


            $stmt->execute([
                ':empresa_id' =>
                $empresaId,

                ':filial_id' =>
                $filialId,

                ':caixa_id' =>
                $caixaId,

                ':usuario_id' =>
                $usuarioId,

                ':status_id' =>
                $statusAberto,

                ':valor_inicial' =>
                $valorInicial,

                ':observacoes' =>
                $observacoes
            ]);


            $aberturaId =
                (int)$this->pdo
                    ->lastInsertId();


            if ($aberturaId <= 0) {
                throw new RuntimeException(
                    'Não foi possível identificar a abertura do caixa.'
                );
            }


            /*
         * Abrir caixa e definir saldo.
         */
            $stmt =
                $this->pdo->prepare("
                UPDATE caixas

                SET
                    status_id = :status_id,
                    saldo_atual = :valor_inicial

                WHERE id = :caixa_id
                  AND empresa_id = :empresa_id
            ");


            $stmt->execute([
                ':status_id' =>
                $statusAberto,

                ':valor_inicial' =>
                $valorInicial,

                ':caixa_id' =>
                $caixaId,

                ':empresa_id' =>
                $empresaId
            ]);


            if ($stmt->rowCount() !== 1) {
                throw new RuntimeException(
                    'Não foi possível abrir o caixa.'
                );
            }


            $aberturaDepois =
                $this->buscarAberturaAuditoria(
                    $aberturaId,
                    $empresaId
                );

            $caixaDepois =
                $this->buscarCaixaAuditoria(
                    $caixaId,
                    $empresaId
                );


            if (
                !$aberturaDepois
                || !$caixaDepois
            ) {
                throw new RuntimeException(
                    'Não foi possível carregar os dados da abertura para auditoria.'
                );
            }


            $this->auditoria->insercao(
                $empresaId,
                $usuarioId,
                'abertura_caixa',
                $aberturaId,
                $aberturaDepois
            );


            $this->auditoria->atualizacao(
                $empresaId,
                $usuarioId,
                'caixas',
                $caixaId,
                $this->dadosAuditoriaCaixa(
                    $caixaAntes
                ),
                $this->dadosAuditoriaCaixa(
                    $caixaDepois
                )
            );


            $this->log->registrar(
                $empresaId,
                $usuarioId,
                'CAIXAS',
                'ABRIR',
                'Caixa aberto: '
                    . $caixaDepois['nome']
                    . ' com saldo inicial de '
                    . number_format(
                        $valorInicial,
                        2,
                        ',',
                        '.'
                    )
                    . ' MZN',
                $caixaId,
                $filialId
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


    private function statusBloqueado(): int
    {
        $stmt = $this->pdo->prepare("
        SELECT id
        FROM status
        WHERE tipo='caixa'
        AND nome='Bloqueado'
        LIMIT 1
    ");

        $stmt->execute();

        return (int)$stmt->fetchColumn();
    }

    private function statusFechado(): int
    {

        $stmt = $this->pdo->prepare("

        SELECT id
        FROM status
        WHERE tipo='caixa'
        AND nome='Fechado'
        LIMIT 1

        ");


        $stmt->execute();


        return (int)$stmt->fetchColumn();
    }

    private function aplicarMovimento(array $dados): int
    {
        $empresaId = (int)($dados['empresa_id'] ?? 0);
        $caixaId   = (int)($dados['caixa_id'] ?? 0);
        $usuarioId = (int)($dados['usuario_id'] ?? 0);
        $valor     = (float)($dados['valor'] ?? 0);
        $tipo      = strtoupper(trim((string)($dados['tipo'] ?? '')));

        if ($empresaId <= 0) {
            throw new RuntimeException(
                'Empresa inválida para o movimento.'
            );
        }

        if ($caixaId <= 0) {
            throw new RuntimeException(
                'Caixa inválido.'
            );
        }

        if ($usuarioId <= 0) {
            throw new RuntimeException(
                'Utilizador inválido.'
            );
        }

        if ($valor <= 0) {
            throw new RuntimeException(
                'O valor do movimento deve ser maior que zero.'
            );
        }

        $tiposPermitidos = [
            'ENTRADA',
            'SAIDA',
            'SANGRIA',
            'SUPRIMENTO'
        ];

        if (!in_array($tipo, $tiposPermitidos, true)) {
            throw new RuntimeException(
                'Tipo de movimento inválido.'
            );
        }

        /*
    |--------------------------------------------------------------------------
    | Buscar e bloquear o caixa
    |--------------------------------------------------------------------------
    */

        $stmt = $this->pdo->prepare("
        SELECT
            id,
            filial_id,
            status_id,
            saldo_atual
        FROM caixas
        WHERE id = :id
          AND empresa_id = :empresa_id
        FOR UPDATE
    ");

        $stmt->execute([
            ':id'         => $caixaId,
            ':empresa_id' => $empresaId
        ]);

        $caixa = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$caixa) {
            throw new RuntimeException(
                'Caixa não encontrado.'
            );
        }

        if ((int)$caixa['status_id'] !== $this->statusAberto()) {
            throw new RuntimeException(
                'O caixa encontra-se fechado.'
            );
        }

        /*
    |--------------------------------------------------------------------------
    | Buscar abertura ativa do caixa
    |--------------------------------------------------------------------------
    */

        $stmt = $this->pdo->prepare("
        SELECT id
        FROM abertura_caixa
        WHERE caixa_id = :caixa_id
          AND empresa_id = :empresa_id
          AND data_fechamento IS NULL
        ORDER BY id DESC
        LIMIT 1
        FOR UPDATE
    ");

        $stmt->execute([
            ':caixa_id'   => $caixaId,
            ':empresa_id' => $empresaId
        ]);

        $aberturaId = $stmt->fetchColumn();

        if ($aberturaId === false) {
            throw new RuntimeException(
                'Não existe abertura ativa para o caixa #' . $caixaId . '.'
            );
        }

        $aberturaId = (int)$aberturaId;

        if ($aberturaId <= 0) {
            throw new RuntimeException(
                'A abertura ativa do caixa é inválida.'
            );
        }

        /*
    |--------------------------------------------------------------------------
    | Calcular entrada ou saída
    |--------------------------------------------------------------------------
    */

        $saldoAtual = (float)$caixa['saldo_atual'];

        $movimentoSaida = in_array(
            $tipo,
            ['SAIDA', 'SANGRIA'],
            true
        );

        if ($movimentoSaida && $saldoAtual < $valor) {
            throw new RuntimeException(
                'Saldo insuficiente.'
            );
        }

        $variacao = $movimentoSaida
            ? -$valor
            : $valor;

        /*
    |--------------------------------------------------------------------------
    | Registar movimento
    |--------------------------------------------------------------------------
    */

        $stmt = $this->pdo->prepare("
        INSERT INTO movimentos_caixa
        (
            empresa_id,
            filial_id,
            caixa_id,
            abertura_id,
            usuario_id,
            tipo,
            origem,
            descricao,
            valor,
            data_movimento
        )
        VALUES
        (
            :empresa_id,
            :filial_id,
            :caixa_id,
            :abertura_id,
            :usuario_id,
            :tipo,
            :origem,
            :descricao,
            :valor,
            NOW()
        )
    ");

        $stmt->execute([
            ':empresa_id' => $empresaId,

            ':filial_id' => !empty($caixa['filial_id'])
                ? (int)$caixa['filial_id']
                : null,

            ':caixa_id' => $caixaId,

            ':abertura_id' => $aberturaId,

            ':usuario_id' => $usuarioId,

            ':tipo' => $tipo,

            ':origem' => $dados['origem'] ?? 'MANUAL',

            ':descricao' => !empty($dados['descricao'])
                ? trim((string)$dados['descricao'])
                : null,

            ':valor' => $valor
        ]);

        $movimentoId =
            (int)$this->pdo
                ->lastInsertId();


        if ($movimentoId <= 0) {
            throw new RuntimeException(
                'Não foi possível identificar o movimento do caixa.'
            );
        }

        /*
    |--------------------------------------------------------------------------
    | Atualizar saldo
    |--------------------------------------------------------------------------
    */

        $stmt = $this->pdo->prepare("
        UPDATE caixas
        SET saldo_atual = saldo_atual + :variacao
        WHERE id = :id
          AND empresa_id = :empresa_id
    ");

        $stmt->execute([
            ':variacao'   => $variacao,
            ':id'         => $caixaId,
            ':empresa_id' => $empresaId
        ]);

        if ($stmt->rowCount() !== 1) {
            throw new RuntimeException(
                'Não foi possível atualizar o saldo do caixa.'
            );
        }
        return $movimentoId;
    }


    public function transferir(
        array $dados
    ): bool {

        $empresaId =
            (int)($dados['empresa_id'] ?? 0);

        $usuarioId =
            (int)($dados['usuario_id'] ?? 0);

        $origemId =
            (int)(
                $dados['caixa_origem_id']
                ?? 0
            );

        $destinoId =
            (int)(
                $dados['caixa_destino_id']
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
            $origemId <= 0
            || $destinoId <= 0
        ) {
            throw new RuntimeException(
                'Selecione os caixas de origem e destino.'
            );
        }

        if ($origemId === $destinoId) {
            throw new RuntimeException(
                'O caixa de origem e destino devem ser diferentes.'
            );
        }

        if ($valor <= 0) {
            throw new RuntimeException(
                'O valor da transferência deve ser maior que zero.'
            );
        }


        $this->pdo->beginTransaction();

        try {

            /*
         * Carregar os dois caixas antes.
         */
            $caixaOrigemAntes =
                $this->buscarCaixaAuditoria(
                    $origemId,
                    $empresaId
                );

            $caixaDestinoAntes =
                $this->buscarCaixaAuditoria(
                    $destinoId,
                    $empresaId
                );


            if (
                !$caixaOrigemAntes
                || !$caixaDestinoAntes
            ) {
                throw new RuntimeException(
                    'Um dos caixas não pertence à empresa atual.'
                );
            }


            /*
         * Registar transferência.
         */
            $stmt =
                $this->pdo->prepare("
                INSERT INTO transferencias_caixa
                (
                    empresa_id,
                    filial_id,
                    caixa_origem_id,
                    caixa_destino_id,
                    usuario_id,
                    valor,
                    descricao
                )
                VALUES
                (
                    :empresa_id,
                    :filial_id,
                    :caixa_origem_id,
                    :caixa_destino_id,
                    :usuario_id,
                    :valor,
                    :descricao
                )
            ");


            $stmt->execute([
                ':empresa_id' =>
                $empresaId,

                ':filial_id' =>
                !empty($caixaOrigemAntes['filial_id'])
                    ? (int)$caixaOrigemAntes['filial_id']
                    : null,

                ':caixa_origem_id' =>
                $origemId,

                ':caixa_destino_id' =>
                $destinoId,

                ':usuario_id' =>
                $usuarioId,

                ':valor' =>
                $valor,

                ':descricao' =>
                !empty($dados['descricao'])
                    ? trim(
                        (string)$dados['descricao']
                    )
                    : null
            ]);


            $transferenciaId =
                (int)$this->pdo
                    ->lastInsertId();


            if ($transferenciaId <= 0) {
                throw new RuntimeException(
                    'Não foi possível identificar a transferência.'
                );
            }


            /*
         * Origem: saída.
         */
            $movimentoSaidaId =
                $this->aplicarMovimento([
                    'empresa_id' =>
                    $empresaId,

                    'caixa_id' =>
                    $origemId,

                    'usuario_id' =>
                    $usuarioId,

                    'tipo' =>
                    'SAIDA',

                    'origem' =>
                    'TRANSFERENCIA',

                    'descricao' =>
                    'Transferência para o caixa #'
                        . $destinoId,

                    'valor' =>
                    $valor
                ]);


            /*
         * Destino: entrada.
         */
            $movimentoEntradaId =
                $this->aplicarMovimento([
                    'empresa_id' =>
                    $empresaId,

                    'caixa_id' =>
                    $destinoId,

                    'usuario_id' =>
                    $usuarioId,

                    'tipo' =>
                    'ENTRADA',

                    'origem' =>
                    'TRANSFERENCIA',

                    'descricao' =>
                    'Transferência recebida do caixa #'
                        . $origemId,

                    'valor' =>
                    $valor
                ]);


            $transferencia =
                $this->buscarTransferenciaAuditoria(
                    $transferenciaId,
                    $empresaId
                );

            $movimentoSaida =
                $this->buscarMovimentoAuditoria(
                    $movimentoSaidaId,
                    $empresaId
                );

            $movimentoEntrada =
                $this->buscarMovimentoAuditoria(
                    $movimentoEntradaId,
                    $empresaId
                );

            $caixaOrigemDepois =
                $this->buscarCaixaAuditoria(
                    $origemId,
                    $empresaId
                );

            $caixaDestinoDepois =
                $this->buscarCaixaAuditoria(
                    $destinoId,
                    $empresaId
                );


            if (
                !$transferencia
                || !$movimentoSaida
                || !$movimentoEntrada
                || !$caixaOrigemDepois
                || !$caixaDestinoDepois
            ) {
                throw new RuntimeException(
                    'Não foi possível carregar a transferência para auditoria.'
                );
            }


            /*
         * Auditoria da transferência.
         */
            $this->auditoria->insercao(
                $empresaId,
                $usuarioId,
                'transferencias_caixa',
                $transferenciaId,
                $transferencia
            );


            /*
         * Auditoria dos dois movimentos.
         */
            $this->auditoria->insercao(
                $empresaId,
                $usuarioId,
                'movimentos_caixa',
                $movimentoSaidaId,
                $movimentoSaida
            );


            $this->auditoria->insercao(
                $empresaId,
                $usuarioId,
                'movimentos_caixa',
                $movimentoEntradaId,
                $movimentoEntrada
            );


            /*
         * Auditoria dos dois saldos.
         */
            $this->auditoria->atualizacao(
                $empresaId,
                $usuarioId,
                'caixas',
                $origemId,
                $this->dadosAuditoriaCaixa(
                    $caixaOrigemAntes
                ),
                $this->dadosAuditoriaCaixa(
                    $caixaOrigemDepois
                )
            );


            $this->auditoria->atualizacao(
                $empresaId,
                $usuarioId,
                'caixas',
                $destinoId,
                $this->dadosAuditoriaCaixa(
                    $caixaDestinoAntes
                ),
                $this->dadosAuditoriaCaixa(
                    $caixaDestinoDepois
                )
            );


            /*
         * Um único log funcional.
         */
            $this->log->registrar(
                $empresaId,
                $usuarioId,
                'CAIXAS',
                'TRANSFERIR',
                'Transferência de '
                    . number_format(
                        $valor,
                        2,
                        ',',
                        '.'
                    )
                    . ' MZN do caixa '
                    . $caixaOrigemDepois['nome']
                    . ' para '
                    . $caixaDestinoDepois['nome'],
                $transferenciaId,
                !empty($caixaOrigemDepois['filial_id'])
                    ? (int)$caixaOrigemDepois['filial_id']
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


    private function buscarCaixaAuditoria(
        int $id,
        int $empresaId
    ): ?array {

        $stmt =
            $this->pdo->prepare("
            SELECT *

            FROM caixas

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


    private function buscarAberturaAuditoria(
        int $id,
        int $empresaId
    ): ?array {

        $stmt =
            $this->pdo->prepare("
            SELECT *

            FROM abertura_caixa

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


    private function buscarMovimentoAuditoria(
        int $id,
        int $empresaId
    ): ?array {

        $stmt =
            $this->pdo->prepare("
            SELECT *

            FROM movimentos_caixa

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


    private function buscarTransferenciaAuditoria(
        int $id,
        int $empresaId
    ): ?array {

        $stmt =
            $this->pdo->prepare("
            SELECT *

            FROM transferencias_caixa

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


    private function dadosAuditoriaCaixa(
        array $caixa
    ): array {

        return [
            'id' =>
            isset($caixa['id'])
                ? (int)$caixa['id']
                : null,

            'empresa_id' =>
            isset($caixa['empresa_id'])
                ? (int)$caixa['empresa_id']
                : null,

            'filial_id' =>
            !empty($caixa['filial_id'])
                ? (int)$caixa['filial_id']
                : null,

            'status_id' =>
            isset($caixa['status_id'])
                ? (int)$caixa['status_id']
                : null,

            'nome' =>
            $caixa['nome']
                ?? null,

            'codigo' =>
            $caixa['codigo']
                ?? null,

            'descricao' =>
            $caixa['descricao']
                ?? null,

            'localizacao' =>
            $caixa['localizacao']
                ?? null,

            'saldo_inicial' =>
            isset($caixa['saldo_inicial'])
                ? (float)$caixa['saldo_inicial']
                : 0.0,

            'saldo_atual' =>
            isset($caixa['saldo_atual'])
                ? (float)$caixa['saldo_atual']
                : 0.0
        ];
    }

    public function extrato(
        int $caixaId,
        int $empresaId,
        array $filtros = []
    ): array {

        $sql = "
        SELECT
            m.*,
            u.nome AS operador
        FROM movimentos_caixa m
        LEFT JOIN usuarios u
            ON u.id = m.usuario_id
        WHERE m.caixa_id = :caixa_id
          AND m.empresa_id = :empresa_id
    ";

        $params = [
            ':caixa_id' => $caixaId,
            ':empresa_id' => $empresaId
        ];

        if (!empty($filtros['inicio'])) {
            $sql .= "
            AND DATE(m.data_movimento) >= :inicio
        ";

            $params[':inicio'] = $filtros['inicio'];
        }

        if (!empty($filtros['fim'])) {
            $sql .= "
            AND DATE(m.data_movimento) <= :fim
        ";

            $params[':fim'] = $filtros['fim'];
        }

        if (!empty($filtros['tipo'])) {
            $sql .= "
            AND m.tipo = :tipo
        ";

            $params[':tipo'] = strtoupper(
                trim((string)$filtros['tipo'])
            );
        }

        $sql .= "
        ORDER BY m.data_movimento DESC,
                 m.id DESC
    ";

        $stmt = $this->pdo->prepare($sql);

        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    // ==================================================
    // SALDO ACTUAL DO CAIXA
    // ==================================================

    public function saldoAtual(
        int $caixaId,
        int $empresaId
    ): float {

        $stmt = $this->pdo->prepare("
        SELECT saldo_atual
        FROM caixas
        WHERE id = :caixa_id
          AND empresa_id = :empresa_id
        LIMIT 1
    ");

        $stmt->execute([
            ':caixa_id' => $caixaId,
            ':empresa_id' => $empresaId
        ]);

        $saldo = $stmt->fetchColumn();

        if ($saldo === false) {
            throw new RuntimeException(
                'Caixa não encontrado.'
            );
        }

        return (float)$saldo;
    }

    public function movimento(
        array $dados
    ): bool {

        $empresaId =
            (int)($dados['empresa_id'] ?? 0);

        $usuarioId =
            (int)($dados['usuario_id'] ?? 0);

        $caixaId =
            (int)($dados['caixa_id'] ?? 0);


        if ($empresaId <= 0) {
            throw new RuntimeException(
                'Empresa inválida.'
            );
        }

        if ($usuarioId <= 0) {
            throw new RuntimeException(
                'Utilizador não autenticado.'
            );
        }

        if ($caixaId <= 0) {
            throw new RuntimeException(
                'Caixa inválido.'
            );
        }


        $this->pdo->beginTransaction();

        try {

            $caixaAntes =
                $this->buscarCaixaAuditoria(
                    $caixaId,
                    $empresaId
                );


            if (!$caixaAntes) {
                throw new RuntimeException(
                    'Caixa não encontrado.'
                );
            }


            $movimentoId =
                $this->aplicarMovimento(
                    $dados
                );


            $movimento =
                $this->buscarMovimentoAuditoria(
                    $movimentoId,
                    $empresaId
                );

            $caixaDepois =
                $this->buscarCaixaAuditoria(
                    $caixaId,
                    $empresaId
                );


            if (
                !$movimento
                || !$caixaDepois
            ) {
                throw new RuntimeException(
                    'Não foi possível carregar o movimento para auditoria.'
                );
            }


            $this->auditoria->insercao(
                $empresaId,
                $usuarioId,
                'movimentos_caixa',
                $movimentoId,
                $movimento
            );


            $this->auditoria->atualizacao(
                $empresaId,
                $usuarioId,
                'caixas',
                $caixaId,
                $this->dadosAuditoriaCaixa(
                    $caixaAntes
                ),
                $this->dadosAuditoriaCaixa(
                    $caixaDepois
                )
            );


            $tipo =
                strtoupper(
                    trim(
                        (string)(
                            $dados['tipo']
                            ?? ''
                        )
                    )
                );

            $valor =
                round(
                    (float)(
                        $dados['valor']
                        ?? 0
                    ),
                    2
                );


            $this->log->registrar(
                $empresaId,
                $usuarioId,
                'CAIXAS',
                'MOVIMENTO',
                'Movimento '
                    . $tipo
                    . ' no caixa '
                    . $caixaDepois['nome']
                    . ' no valor de '
                    . number_format(
                        $valor,
                        2,
                        ',',
                        '.'
                    )
                    . ' MZN',
                $movimentoId,
                !empty($caixaDepois['filial_id'])
                    ? (int)$caixaDepois['filial_id']
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
            nome
        FROM status
        WHERE tipo = 'caixa'
        ORDER BY nome ASC
    ");

        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function statusAberto(): int
    {

        $stmt = $this->pdo->prepare("
        SELECT id
        FROM status
        WHERE tipo='caixa'
        AND nome='Aberto'
        LIMIT 1
    ");

        $stmt->execute();

        return (int)$stmt->fetchColumn();
    }
}
