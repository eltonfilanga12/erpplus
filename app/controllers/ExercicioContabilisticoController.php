<?php

declare(strict_types=1);

require_once __DIR__
    . '/../models/ExercicioContabilistico.php';

final class ExercicioContabilisticoController
{
    private ExercicioContabilistico $exercicio;

    public function __construct(PDO $pdo)
    {
        $this->exercicio =
            new ExercicioContabilistico($pdo);
    }

    // ==================================================
    // EXERCÍCIOS
    // ==================================================

    public function listar(): array
    {
        return $this->exercicio->listar(
            $this->empresaId()
        );
    }

    public function buscar(
        int $id
    ): ?array {

        $this->validarId(
            $id,
            'Exercício contabilístico inválido.'
        );

        return $this->exercicio->buscar(
            $id,
            $this->empresaId()
        );
    }

    public function guardar(
        array $dados
    ): int {

        $dados['empresa_id'] =
            $this->empresaId();

        $dados['usuario_id'] =
            $this->usuarioId();

        unset(
            $dados['filial_id'],
            $dados['status_id'],
            $dados['estado']
        );

        return $this->exercicio->guardar(
            $dados
        );
    }

    public function eliminar(
        int $id
    ): bool {

        $this->validarId(
            $id,
            'Exercício contabilístico inválido.'
        );

        return $this->exercicio->eliminar(
            $id,
            $this->empresaId(),
            $this->usuarioId()
        );
    }

    public function gerarPeriodos(
        int $exercicioId
    ): int {

        $this->validarId(
            $exercicioId,
            'Exercício contabilístico inválido.'
        );

        return $this->exercicio->gerarPeriodos(
            $exercicioId,
            $this->empresaId(),
            $this->usuarioId()
        );
    }

    public function alterarStatus(
        int $id,
        string $status
    ): bool {

        $this->validarId(
            $id,
            'Exercício contabilístico inválido.'
        );

        $status = trim($status);

        if ($status === '') {
            throw new RuntimeException(
                'Informe o novo status do exercício.'
            );
        }

        return $this->exercicio->alterarStatus(
            $id,
            $this->empresaId(),
            $status,
            $this->usuarioId()
        );
    }

    // ==================================================
    // PERÍODOS
    // ==================================================

    public function listarPeriodos(
        int $exercicioId
    ): array {

        $this->validarId(
            $exercicioId,
            'Exercício contabilístico inválido.'
        );

        return $this->exercicio->listarPeriodos(
            $exercicioId,
            $this->empresaId()
        );
    }

    public function buscarPeriodo(
        int $id
    ): ?array {

        $this->validarId(
            $id,
            'Período contabilístico inválido.'
        );

        return $this->exercicio->buscarPeriodo(
            $id,
            $this->empresaId()
        );
    }

    public function alterarStatusPeriodo(
        int $id,
        string $status
    ): bool {

        $this->validarId(
            $id,
            'Período contabilístico inválido.'
        );

        $status = trim($status);

        if ($status === '') {
            throw new RuntimeException(
                'Informe o novo status do período.'
            );
        }

        return $this->exercicio
            ->alterarStatusPeriodo(
                $id,
                $this->empresaId(),
                $status,
                $this->usuarioId()
            );
    }

    // ==================================================
    // STATUS
    // ==================================================

    public function statusExercicios(): array
    {
        return $this->exercicio
            ->listarStatus(
                'exercicio_contabilistico'
            );
    }

    public function statusPeriodos(): array
    {
        return $this->exercicio
            ->listarStatus(
                'periodo_contabilistico'
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
}
