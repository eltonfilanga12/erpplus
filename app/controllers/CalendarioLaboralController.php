<?php

declare(strict_types=1);

require_once __DIR__
    . '/../models/CalendarioLaboral.php';

final class CalendarioLaboralController
{
    private CalendarioLaboral $model;

    public function __construct(PDO $pdo)
    {
        $this->model =
            new CalendarioLaboral(
                $pdo
            );
    }

    // ==================================================
    // DADOS INICIAIS
    // ==================================================

    public function dadosIniciais(): array
    {
        return $this->model
            ->dadosIniciais(
                $this->empresaId()
            );
    }

    // ==================================================
    // LISTAR
    // ==================================================

    public function listar(
        array $filtros = []
    ): array {

        return $this->model
            ->listar(
                $this->empresaId(),
                $filtros
            );
    }

    // ==================================================
    // BUSCAR
    // ==================================================

    public function buscar(
        int $eventoId
    ): array {

        $this->validarEventoId(
            $eventoId
        );

        return $this->model
            ->buscar(
                $this->empresaId(),
                $eventoId
            );
    }

    // ==================================================
    // CRIAR
    // ==================================================

    public function criar(
        array $dados
    ): array {

        $empresaId =
            $this->empresaId();

        $eventoId =
            $this->model
                ->criar(
                    $empresaId,
                    $this->usuarioId(),
                    $dados
                );

        return [
            'id' =>
                $eventoId,

            'evento' =>
                $this->model
                    ->buscar(
                        $empresaId,
                        $eventoId
                    )
        ];
    }

    // ==================================================
    // EDITAR
    // ==================================================

    public function editar(
        int $eventoId,
        array $dados
    ): array {

        $this->validarEventoId(
            $eventoId
        );

        $empresaId =
            $this->empresaId();

        $this->model
            ->editar(
                $empresaId,
                $this->usuarioId(),
                $eventoId,
                $dados
            );

        return [
            'id' =>
                $eventoId,

            'evento' =>
                $this->model
                    ->buscar(
                        $empresaId,
                        $eventoId
                    )
        ];
    }

    // ==================================================
    // ELIMINAR
    // ==================================================

    public function eliminar(
        int $eventoId
    ): array {

        $this->validarEventoId(
            $eventoId
        );

        $this->model
            ->eliminar(
                $this->empresaId(),
                $eventoId
            );

        return [
            'id' =>
                $eventoId
        ];
    }

    // ==================================================
    // FILTROS
    // ==================================================

    public function normalizarFiltros(
        array $dados
    ): array {

        return [
            'data_inicio' =>
                $this->textoOpcional(
                    $dados['data_inicio']
                    ?? null
                ),

            'data_fim' =>
                $this->textoOpcional(
                    $dados['data_fim']
                    ?? null
                ),

            'filial_id' =>
                $this->inteiroOpcional(
                    $dados['filial_id']
                    ?? null
                ),

            'tipo_evento' =>
                $this->textoOpcional(
                    $dados['tipo_evento']
                    ?? null
                ),

            'status_id' =>
                $this->inteiroOpcional(
                    $dados['status_id']
                    ?? null
                )
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

    // ==================================================
    // VALIDAR ID
    // ==================================================

    private function validarEventoId(
        int $eventoId
    ): void {

        if ($eventoId <= 0) {
            throw new RuntimeException(
                'Evento do calendário laboral inválido.'
            );
        }
    }

    // ==================================================
    // HELPERS
    // ==================================================

    private function inteiroOpcional(
        mixed $valor
    ): ?int {

        if (
            $valor === null
            || trim((string)$valor) === ''
        ) {
            return null;
        }

        $inteiro =
            filter_var(
                $valor,
                FILTER_VALIDATE_INT
            );

        if (
            $inteiro === false
            || $inteiro <= 0
        ) {
            return null;
        }

        return (int)$inteiro;
    }

    private function textoOpcional(
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
}