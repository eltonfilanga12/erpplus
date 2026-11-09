<?php

declare(strict_types=1);

final class PeriodoContabilistico
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    // ==================================================
    // OBTÉM O PERÍODO PELO ID
    // ==================================================

    public function obterPorId(
        int $empresaId,
        int $periodoId
    ): array {

        $sql = "
            SELECT
                p.*,
                s.nome AS status_nome,
                e.codigo AS exercicio_codigo,
                e.descricao AS exercicio_descricao
            FROM periodos_contabilisticos p
            INNER JOIN exercicios_contabilisticos e
                ON e.id = p.exercicio_id
            INNER JOIN status s
                ON s.id = p.status_id
            WHERE p.id = :id
              AND p.empresa_id = :empresa
            LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);

        $stmt->execute([
            ':id'      => $periodoId,
            ':empresa' => $empresaId
        ]);

        $periodo =
            $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$periodo) {

            throw new RuntimeException(
                'Período contabilístico não encontrado.'
            );
        }

        return $periodo;
    }

    // ==================================================
    // OBTÉM O PERÍODO PELA DATA
    // ==================================================

    public function obterPorData(
        int $empresaId,
        string $data
    ): array {

        $sql = "
            SELECT
                p.*,
                s.nome AS status_nome,
                e.codigo AS exercicio_codigo,
                e.descricao AS exercicio_descricao
            FROM periodos_contabilisticos p
            INNER JOIN exercicios_contabilisticos e
                ON e.id = p.exercicio_id
            INNER JOIN status s
                ON s.id = p.status_id
            WHERE p.empresa_id = :empresa
              AND :data BETWEEN p.data_inicio
                            AND p.data_fim
            LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);

        $stmt->execute([
            ':empresa' => $empresaId,
            ':data'    => $data
        ]);

        $periodo =
            $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$periodo) {

            throw new RuntimeException(
                'Não existe período contabilístico para a data informada.'
            );
        }

        return $periodo;
    }

    // ==================================================
    // OBTÉM PELO NÚMERO
    // ==================================================

    public function obterPorNumero(
        int $empresaId,
        int $exercicioId,
        int $numero
    ): array {

        $sql = "
            SELECT
                p.*,
                s.nome AS status_nome
            FROM periodos_contabilisticos p
            INNER JOIN status s
                ON s.id = p.status_id
            WHERE p.empresa_id = :empresa
              AND p.exercicio_id = :exercicio
              AND p.numero = :numero
            LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);

        $stmt->execute([
            ':empresa'   => $empresaId,
            ':exercicio' => $exercicioId,
            ':numero'    => $numero
        ]);

        $periodo =
            $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$periodo) {

            throw new RuntimeException(
                'Período contabilístico inexistente.'
            );
        }

        return $periodo;
    }

    // ==================================================
    // DEVOLVE APENAS O ID
    // ==================================================

    public function idPorData(
        int $empresaId,
        string $data
    ): int {

        return (int)
        $this->obterPorData(
            $empresaId,
            $data
        )['id'];
    }

    // ==================================================
    // DEVOLVE O NÚMERO
    // ==================================================

    public function numeroPorData(
        int $empresaId,
        string $data
    ): int {

        return (int)
        $this->obterPorData(
            $empresaId,
            $data
        )['numero'];
    }

    // ==================================================
    // DEVOLVE O NOME
    // ==================================================

    public function nomePorData(
        int $empresaId,
        string $data
    ): string {

        return (string)
        $this->obterPorData(
            $empresaId,
            $data
        )['nome'];
    }

    // ==================================================
    // DEVOLVE O EXERCÍCIO
    // ==================================================

    public function exercicioIdPorData(
        int $empresaId,
        string $data
    ): int {

        return (int)
        $this->obterPorData(
            $empresaId,
            $data
        )['exercicio_id'];
    }

    // ==================================================
    // OBTÉM O PERÍODO ABERTO
    // ==================================================

    public function obterAberto(
        int $empresaId
    ): array {

        $sql = "
            SELECT
                p.*,
                s.nome AS status_nome,
                e.codigo AS exercicio_codigo
            FROM periodos_contabilisticos p
            INNER JOIN status s
                ON s.id = p.status_id
            INNER JOIN exercicios_contabilisticos e
                ON e.id = p.exercicio_id
            WHERE p.empresa_id = :empresa
              AND LOWER(s.nome)
                    IN
                    (
                        'aberto',
                        'activo',
                        'ativo'
                    )
            ORDER BY
                p.data_inicio DESC
            LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);

        $stmt->execute([
            ':empresa' => $empresaId
        ]);

        $periodo =
            $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$periodo) {

            throw new RuntimeException(
                'Não existe período contabilístico aberto.'
            );
        }

        return $periodo;
    }

    // ==================================================
    // LISTAR TODOS
    // ==================================================

    public function listar(
        int $empresaId
    ): array {

        $sql = "
            SELECT
                p.*,
                s.nome AS status_nome,
                e.codigo AS exercicio_codigo
            FROM periodos_contabilisticos p
            INNER JOIN status s
                ON s.id = p.status_id
            INNER JOIN exercicios_contabilisticos e
                ON e.id = p.exercicio_id
            WHERE p.empresa_id = :empresa
            ORDER BY
                p.data_inicio DESC
        ";

        $stmt = $this->pdo->prepare($sql);

        $stmt->execute([
            ':empresa' => $empresaId
        ]);

        return $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );
    }

    // ==================================================
    // LISTAR POR EXERCÍCIO
    // ==================================================

    public function listarPorExercicio(
        int $empresaId,
        int $exercicioId
    ): array {

        $sql = "
            SELECT
                p.*,
                s.nome AS status_nome
            FROM periodos_contabilisticos p
            INNER JOIN status s
                ON s.id = p.status_id
            WHERE p.empresa_id = :empresa
              AND p.exercicio_id = :exercicio
            ORDER BY
                p.numero
        ";

        $stmt = $this->pdo->prepare($sql);

        $stmt->execute([
            ':empresa'   => $empresaId,
            ':exercicio' => $exercicioId
        ]);

        return $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );
    }

    // ==================================================
    // EXISTE?
    // ==================================================

    public function existe(
        int $empresaId,
        int $periodoId
    ): bool {

        $sql = "
            SELECT COUNT(*)
            FROM periodos_contabilisticos
            WHERE empresa_id = :empresa
              AND id = :id
        ";

        $stmt = $this->pdo->prepare($sql);

        $stmt->execute([
            ':empresa' => $empresaId,
            ':id'      => $periodoId
        ]);

        return
            (int)$stmt->fetchColumn() > 0;
    }

    // ==================================================
    // VALIDA SE ESTÁ ABERTO
    // ==================================================

    public function validarAberto(
        array $periodo
    ): void {

        $status =
            mb_strtolower(
                (string)(
                    $periodo['status_nome']
                    ?? ''
                )
            );

        if (
            !in_array(
                $status,
                [
                    'aberto',
                    'activo',
                    'ativo'
                ],
                true
            )
        ) {

            throw new RuntimeException(
                sprintf(
                    'O período contabilístico "%s" encontra-se fechado.',
                    $periodo['nome']
                )
            );
        }
    }

    // ==================================================
    // VALIDA UMA DATA
    // ==================================================

    public function validarData(
        int $empresaId,
        string $data
    ): void {

        $periodo =
            $this->obterPorData(
                $empresaId,
                $data
            );

        $this->validarAberto(
            $periodo
        );
    }
}
