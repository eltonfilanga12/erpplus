<?php

declare(strict_types=1);

final class CentroCustoController
{
    private CentroCusto $centroCusto;

    public function __construct(PDO $pdo)
    {
        $this->centroCusto =
            new CentroCusto($pdo);
    }

    // ==================================================
    // LISTAR
    // ==================================================

    public function listar(
        array $dados = []
    ): array {

        $empresaId =
            $this->empresaId();

        return $this->centroCusto->listar(
            $empresaId,
            $this->prepararFiltros($dados)
        );
    }

    // ==================================================
    // BUSCAR
    // ==================================================

    public function buscar(
        array $dados
    ): ?array {

        $id = (int)(
            $dados['id'] ?? 0
        );

        if ($id <= 0) {
            throw new RuntimeException(
                'Centro de custo inválido.'
            );
        }

        return $this->centroCusto->buscar(
            $id,
            $this->empresaId()
        );
    }

    // ==================================================
    // GUARDAR
    // ==================================================

    public function guardar(
        array $dados
    ): array {

        $dados['empresa_id'] =
            $this->empresaId();

        $dados['usuario_id'] =
            $this->usuarioId();

        /*
         * Se o utilizador estiver associado a uma filial,
         * o centro de custo ficará limitado a essa filial.
         */

        $filialSessao =
            Auth::filialId();

        if (
            $filialSessao !== null
            && $filialSessao > 0
        ) {
            $dados['filial_id'] =
                (int)$filialSessao;
        }

        if (!empty($dados['id'])) {
            $atualizado =
                $this->centroCusto->atualizar(
                    $dados
                );

            return [
                'id' =>
                (int)$dados['id'],

                'atualizado' =>
                $atualizado,

                'mensagem' =>
                'Centro de custo actualizado com sucesso.'
            ];
        }

        $id =
            $this->centroCusto->criar(
                $dados
            );

        return [
            'id' =>
            $id,

            'criado' =>
            true,

            'mensagem' =>
            'Centro de custo criado com sucesso.'
        ];
    }

    // ==================================================
    // ELIMINAR
    // ==================================================

    public function eliminar(
        array $dados
    ): array {

        $id = (int)(
            $dados['id'] ?? 0
        );

        if ($id <= 0) {
            throw new RuntimeException(
                'Centro de custo inválido.'
            );
        }

        $eliminado =
            $this->centroCusto->eliminar(
                $id,
                $this->empresaId(),
                $this->usuarioId()
            );

        return [
            'eliminado' =>
            $eliminado,

            'mensagem' =>
            'Centro de custo eliminado com sucesso.'
        ];
    }

    // ==================================================
    // RESUMO
    // ==================================================

    public function resumo(
        array $dados = []
    ): array {

        return $this->centroCusto->resumo(
            $this->empresaId(),
            $this->prepararFiltros($dados)
        );
    }

    // ==================================================
    // LISTAR PARA SELECT
    // ==================================================

    public function listarParaSelect(
        array $dados = []
    ): array {

        $excluirId =
            !empty($dados['excluir_id'])
            ? (int)$dados['excluir_id']
            : null;

        return $this->centroCusto
            ->listarParaSelect(
                $this->empresaId(),
                $excluirId
            );
    }

    // ==================================================
    // LISTAR STATUS
    // ==================================================

    public function listarStatus(): array
    {
        return $this->centroCusto
            ->listarStatus();
    }

    // ==================================================
    // RETORNAR EMPRESA DA SESSÃO
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
    // RETORNAR UTILIZADOR DA SESSÃO
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
    // PREPARAR FILTROS
    // ==================================================

    private function prepararFiltros(
        array $dados
    ): array {

        $filtros = [
            'tipo' =>
            !empty($dados['tipo'])
                ? strtoupper(
                    trim(
                        (string)$dados['tipo']
                    )
                )
                : null,

            'filial_id' =>
            !empty($dados['filial_id'])
                ? (int)$dados['filial_id']
                : null,

            'status_id' =>
            !empty($dados['status_id'])
                ? (int)$dados['status_id']
                : null,

            'pesquisa' =>
            !empty($dados['pesquisa'])
                ? trim(
                    (string)$dados['pesquisa']
                )
                : null
        ];

        /*
         * Um utilizador associado a uma filial não poderá
         * consultar centros de outras filiais.
         */

        $filialSessao =
            Auth::filialId();

        if (
            $filialSessao !== null
            && $filialSessao > 0
        ) {
            $filtros['filial_id'] =
                (int)$filialSessao;
        }

        return $filtros;
    }
}
