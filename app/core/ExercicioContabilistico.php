<?php

declare(strict_types=1);

final class ExercicioContabilistico
{
    private PDO $pdo;

    public function __construct(
        PDO $pdo
    ) {
        $this->pdo = $pdo;
    }

    // ==================================================
    // OBTÉM O EXERCÍCIO PELA DATA
    // ==================================================

    public function obterPorData(
        int $empresaId,
        string $data
    ): array {

        $stmt =
            $this->pdo->prepare("
                SELECT
                    ex.*

                FROM exercicios_contabilisticos ex

                INNER JOIN status st
                    ON st.id = ex.status_id

                WHERE ex.empresa_id = :empresa

                  AND :data BETWEEN
                      ex.data_inicio
                  AND ex.data_fim

                LIMIT 1
            ");

        $stmt->execute([
            ':empresa' => $empresaId,
            ':data'    => $data
        ]);

        $exercicio =
            $stmt->fetch(
                PDO::FETCH_ASSOC
            );

        if (!$exercicio) {

            throw new RuntimeException(
                'Não existe exercício contabilístico para a data informada.'
            );
        }

        return $exercicio;
    }

    // ==================================================
    // OBTÉM PELO ID
    // ==================================================

    public function obterPorId(
        int $empresaId,
        int $id
    ): array {

        $stmt =
            $this->pdo->prepare("
                SELECT
                    ex.*

                FROM exercicios_contabilisticos ex

                WHERE ex.id = :id

                  AND ex.empresa_id = :empresa

                LIMIT 1
            ");

        $stmt->execute([
            ':id'       => $id,
            ':empresa'  => $empresaId
        ]);

        $dados =
            $stmt->fetch(
                PDO::FETCH_ASSOC
            );

        if (!$dados) {

            throw new RuntimeException(
                'Exercício contabilístico inexistente.'
            );
        }

        return $dados;
    }

    // ==================================================
    // VALIDA SE ESTÁ ABERTO
    // ==================================================

    public function validarAberto(
        array $exercicio
    ): void {

        $status =
            $this->nomeStatus(
                (int)$exercicio['status_id']
            );

        if (
            mb_strtolower($status)
            !== 'aberto'
        ) {

            throw new RuntimeException(
                'O exercício contabilístico encontra-se encerrado.'
            );
        }
    }

    // ==================================================
    // OBTÉM O STATUS
    // ==================================================

    private function nomeStatus(
        int $statusId
    ): string {

        $stmt =
            $this->pdo->prepare("
                SELECT
                    nome

                FROM status

                WHERE id = :id

                LIMIT 1
            ");

        $stmt->execute([
            ':id' => $statusId
        ]);

        $nome =
            $stmt->fetchColumn();

        if (!$nome) {

            throw new RuntimeException(
                'Status do exercício não encontrado.'
            );
        }

        return (string)$nome;
    }

    // ==================================================
    // DEVOLVE APENAS O ID
    // ==================================================

    public function idPorData(
        int $empresaId,
        string $data
    ): int {

        $exercicio =
            $this->obterPorData(
                $empresaId,
                $data
            );

        return (int)
        $exercicio['id'];
    }

    // ==================================================
    // DEVOLVE O CÓDIGO
    // ==================================================

    public function codigoPorData(
        int $empresaId,
        string $data
    ): string {

        $exercicio =
            $this->obterPorData(
                $empresaId,
                $data
            );

        return (string)
        $exercicio['codigo'];
    }

    // ==================================================
    // OBTÉM O EXERCÍCIO ABERTO
    // ==================================================

    public function obterAberto(
        int $empresaId
    ): array {

        $stmt =
            $this->pdo->prepare("
                SELECT
                    ex.*

                FROM exercicios_contabilisticos ex

                INNER JOIN status st
                    ON st.id = ex.status_id

                WHERE ex.empresa_id = :empresa

                  AND LOWER(st.nome)
                    IN
                    (
                        'aberto',
                        'activo',
                        'ativo'
                    )

                ORDER BY
                    ex.data_inicio DESC

                LIMIT 1
            ");

        $stmt->execute([
            ':empresa' => $empresaId
        ]);

        $dados =
            $stmt->fetch(
                PDO::FETCH_ASSOC
            );

        if (!$dados) {

            throw new RuntimeException(
                'Não existe exercício contabilístico aberto.'
            );
        }

        return $dados;
    }
}
