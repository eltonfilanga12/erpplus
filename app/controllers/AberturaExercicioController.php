<?php

declare(strict_types=1);

require_once __DIR__
    . '/../models/AberturaExercicio.php';

final class AberturaExercicioController
{
    private AberturaExercicio $abertura;

    public function __construct(PDO $pdo)
    {
        $this->abertura =
            new AberturaExercicio(
                $pdo
            );
    }

    // ==================================================
    // LISTAR EXERCÍCIOS
    // ==================================================

    public function listar(): array
    {
        return $this->abertura
            ->listar(
                $this->empresaId()
            );
    }

    // ==================================================
    // DIAGNOSTICAR ABERTURA
    // ==================================================

    public function diagnosticar(
        int $exercicioOrigemId
    ): array {

        if ($exercicioOrigemId <= 0) {
            throw new RuntimeException(
                'Exercício de origem inválido.'
            );
        }

        return $this->abertura
            ->diagnosticar(
                $this->empresaId(),
                $exercicioOrigemId
            );
    }

    // ==================================================
    // GERAR ABERTURA
    // ==================================================

    public function abrir(
        int $exercicioOrigemId
    ): array {

        if ($exercicioOrigemId <= 0) {
            throw new RuntimeException(
                'Exercício de origem inválido.'
            );
        }

        return $this->abertura
            ->abrir(
                $this->empresaId(),
                $exercicioOrigemId,
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

        $exercicioEncerradoId =
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
                        'encerrado',
                        'fechado'
                    ],
                    true
                )

                && !empty($exercicio['encerrado_em'])

                && (int)(
                    $exercicio['quantidade_aberturas'] ?? 0
                ) === 0
            ) {
                $exercicioEncerradoId =
                    (int)$exercicio['id'];

                break;
            }
        }

        return [
            'exercicios' =>
            $exercicios,

            'exercicio_encerrado_id' =>
            $exercicioEncerradoId
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
