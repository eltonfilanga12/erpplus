<?php

declare(strict_types=1);

require_once __DIR__
    . '/../models/DiarioContabilistico.php';

final class DiarioContabilisticoController
{
    private DiarioContabilistico $diario;

    public function __construct(PDO $pdo)
    {
        $this->diario =
            new DiarioContabilistico($pdo);
    }

    // ==================================================
    // LISTAR
    // ==================================================

    public function listar(): array
    {
        return $this->diario->listar(
            $this->empresaId()
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
            'Diário contabilístico inválido.'
        );

        return $this->diario->buscar(
            $id,
            $this->empresaId()
        );
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

        unset(
            $dados['filial_id'],
            $dados['ativo']
        );

        return $this->diario->guardar(
            $dados
        );
    }

    // ==================================================
    // ALTERAR STATUS
    // ==================================================

    public function alterarStatus(
        int $id,
        int $statusId
    ): bool {

        $this->validarId(
            $id,
            'Diário contabilístico inválido.'
        );

        $this->validarId(
            $statusId,
            'Status do diário inválido.'
        );

        return $this->diario
            ->alterarStatus(
                $id,
                $this->empresaId(),
                $statusId,
                $this->usuarioId()
            );
    }

    // ==================================================
    // ELIMINAR
    // ==================================================

    public function eliminar(
        int $id
    ): bool {

        $this->validarId(
            $id,
            'Diário contabilístico inválido.'
        );

        return $this->diario->eliminar(
            $id,
            $this->empresaId(),
            $this->usuarioId()
        );
    }

    // ==================================================
    // STATUS
    // ==================================================

    public function status(): array
    {
        return $this->diario
            ->listarStatus();
    }

    // ==================================================
    // PRÓXIMO NÚMERO
    // ==================================================

    public function proximoNumero(
        int $id
    ): int {

        $this->validarId(
            $id,
            'Diário contabilístico inválido.'
        );

        return $this->diario
            ->proximoNumero(
                $id,
                $this->empresaId()
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
