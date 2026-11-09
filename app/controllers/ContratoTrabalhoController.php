<?php

declare(strict_types=1);

require_once __DIR__
    . '/../models/ContratoTrabalho.php';

final class ContratoTrabalhoController
{
    private ContratoTrabalho $model;

    public function __construct(PDO $pdo)
    {
        $this->model =
            new ContratoTrabalho(
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
        int $contratoId
    ): array {

        $this->validarContratoId(
            $contratoId
        );

        return $this->model
            ->buscar(
                $this->empresaId(),
                $contratoId
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

        $contratoId =
            $this->model
            ->criar(
                $empresaId,
                $this->usuarioId(),
                $dados
            );

        return [
            'id' =>
            $contratoId,

            'contrato' =>
            $this->model
                ->buscar(
                    $empresaId,
                    $contratoId
                )
        ];
    }

    // ==================================================
    // EDITAR
    // ==================================================

    public function editar(
        int $contratoId,
        array $dados
    ): array {

        $this->validarContratoId(
            $contratoId
        );

        $empresaId =
            $this->empresaId();

        $this->model
            ->editar(
                $empresaId,
                $this->usuarioId(),
                $contratoId,
                $dados
            );

        return [
            'id' =>
            $contratoId,

            'contrato' =>
            $this->model
                ->buscar(
                    $empresaId,
                    $contratoId
                )
        ];
    }

    // ==================================================
    // ELIMINAR
    // ==================================================

    public function eliminar(
        int $contratoId
    ): array {

        $this->validarContratoId(
            $contratoId
        );

        $this->model
            ->eliminar(
                $this->empresaId(),
                $contratoId
            );

        return [
            'id' =>
            $contratoId
        ];
    }

    // ==================================================
    // NORMALIZAR FILTROS
    // ==================================================

    public function normalizarFiltros(
        array $dados
    ): array {

        return [
            'funcionario_id' =>
            $this->inteiroOpcional(
                $dados['funcionario_id']
                    ?? null
            ),

            'tipo_contrato_id' =>
            $this->inteiroOpcional(
                $dados['tipo_contrato_id']
                    ?? null
            ),

            'filial_id' =>
            $this->inteiroOpcional(
                $dados['filial_id']
                    ?? null
            ),

            'departamento_id' =>
            $this->inteiroOpcional(
                $dados['departamento_id']
                    ?? null
            ),

            'status_id' =>
            $this->inteiroOpcional(
                $dados['status_id']
                    ?? null
            ),

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

            'pesquisa' =>
            $this->textoOpcional(
                $dados['pesquisa']
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

    private function validarContratoId(
        int $contratoId
    ): void {

        if ($contratoId <= 0) {
            throw new RuntimeException(
                'Contrato de trabalho inválido.'
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
