<?php

declare(strict_types=1);

require_once __DIR__
    . '/../models/Despesa.php';

final class DespesaController
{
    private Despesa $model;

    public function __construct(
        PDO $pdo
    ) {
        $this->model =
            new Despesa(
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

    public function listar(): array
    {
        return $this->model
            ->listar(
                $this->empresaId()
            );
    }

    // ==================================================
    // CONTABILIZAR
    // ==================================================

    public function contabilizar(
        int $id
    ): array {

        $this->validarId(
            $id,
            'Despesa inválida.'
        );


        return $this->model
            ->contabilizar(
                $id,
                $this->empresaId(),
                $this->usuarioId()
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
            'Despesa inválida.'
        );


        return $this->model
            ->buscar(
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

        /*
         * Se o utilizador estiver associado
         * a uma filial, força essa filial.
         */
        $filialSessao =
            $this->filialId();


        if (
            $filialSessao
            !== null
        ) {

            $dados['filial_id'] =
                $filialSessao;
        }


        return $this->model
            ->guardar(
                $this->empresaId(),
                $this->usuarioId(),
                $dados
            );
    }


    // ==================================================
    // ID
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
    // EMPRESA
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


        return
            (int)$empresaId;
    }


    // ==================================================
    // FILIAL
    // ==================================================

    private function filialId(): ?int
    {
        $filialId =
            Auth::filialId();


        return (
            $filialId !== null
            && $filialId > 0
        )
            ? (int)$filialId
            : null;
    }


    // ==================================================
    // UTILIZADOR
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


        return
            (int)$usuarioId;
    }
}
