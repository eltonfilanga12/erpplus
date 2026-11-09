<?php

declare(strict_types=1);

require_once __DIR__
    . '/../models/FechoExercicio.php';

final class FechoExercicioController
{
    private FechoExercicio $fecho;

    public function __construct(PDO $pdo)
    {
        $this->fecho =
            new FechoExercicio($pdo);
    }

    // ==================================================
    // LISTAR EXERCÍCIOS
    // ==================================================

    public function listar(): array
    {
        return $this->fecho
            ->listar(
                $this->empresaId()
            );
    }

    // ==================================================
    // BUSCAR EXERCÍCIO
    // ==================================================

    public function buscar(
        int $exercicioId
    ): array {

        if ($exercicioId <= 0) {
            throw new RuntimeException(
                'Exercício contabilístico inválido.'
            );
        }

        return $this->fecho
            ->buscar(
                $this->empresaId(),
                $exercicioId
            );
    }

    // ==================================================
    // DIAGNOSTICAR EXERCÍCIO
    // ==================================================

    public function diagnosticar(
        int $exercicioId
    ): array {

        if ($exercicioId <= 0) {
            throw new RuntimeException(
                'Exercício contabilístico inválido.'
            );
        }

        return $this->fecho
            ->diagnosticar(
                $this->empresaId(),
                $exercicioId
            );
    }

    // ==================================================
    // FECHAR EXERCÍCIO
    // ==================================================

    public function fechar(
        int $exercicioId
    ): array {

        if ($exercicioId <= 0) {
            throw new RuntimeException(
                'Exercício contabilístico inválido.'
            );
        }

        return $this->fecho
            ->fechar(
                $this->empresaId(),
                $exercicioId,
                $this->usuarioId()
            );
    }

    // ==================================================
    // DADOS INICIAIS
    // ==================================================

    public function dadosIniciais(): array
    {
        $exercicios =
            $this->listar();

        $exercicioAbertoId =
            null;

        foreach ($exercicios as $exercicio) {

            $status =
                mb_strtolower(
                    trim(
                        (string)(
                            $exercicio['status_nome']
                            ?? ''
                        )
                    )
                );

            if (
                in_array(
                    $status,
                    [
                        'aberto',
                        'activo',
                        'ativo'
                    ],
                    true
                )

                && empty($exercicio['encerrado_em'])
            ) {
                $exercicioAbertoId =
                    (int)$exercicio['id'];

                break;
            }
        }

        return [
            'exercicios' =>
            $exercicios,

            'exercicio_aberto_id' =>
            $exercicioAbertoId
        ];
    }

    // ==================================================
    // EMPRESA DA SESSÃO
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

    // ==================================================
    // UTILIZADOR DA SESSÃO
    // ==================================================

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
}
