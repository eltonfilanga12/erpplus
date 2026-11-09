<?php

declare(strict_types=1);

final class RubricaSalarialController
{
    private RubricaSalarial $model;

    public function __construct()
    {
        $this->model = new RubricaSalarial();
    }

    public function listar(
        int $empresaId,
        array $filtros = []
    ): array {
        return [
            'sucesso' => true,
            'dados' => $this->model->listar(
                $empresaId,
                $filtros
            ),
        ];
    }

    public function listarAtivas(
        int $empresaId,
        ?string $tipo = null
    ): array {
        return [
            'sucesso' => true,
            'dados' => $this->model->listarAtivas(
                $empresaId,
                $tipo
            ),
        ];
    }

    public function buscar(
        int $empresaId,
        array $dados
    ): array {
        $id = isset($dados['id'])
            ? (int) $dados['id']
            : 0;

        $uuid = trim(
            (string) ($dados['uuid'] ?? '')
        );

        if ($id <= 0 && $uuid === '') {
            throw new InvalidArgumentException(
                'Informe o ID ou UUID da rubrica.'
            );
        }

        $rubrica = $id > 0
            ? $this->model->buscarPorId(
                $id,
                $empresaId
            )
            : $this->model->buscarPorUuid(
                $uuid,
                $empresaId
            );

        if ($rubrica === null) {
            throw new RuntimeException(
                'Rubrica salarial não encontrada.'
            );
        }

        return [
            'sucesso' => true,
            'dados' => $rubrica,
        ];
    }

    public function criar(
        int $empresaId,
        ?int $usuarioId,
        array $dados
    ): array {
        $rubrica = $this->model->criar(
            $dados,
            $empresaId,
            $usuarioId
        );

        return [
            'sucesso' => true,
            'mensagem' => 'Rubrica salarial criada com sucesso.',
            'dados' => $rubrica,
        ];
    }

    public function atualizar(
        int $empresaId,
        ?int $usuarioId,
        array $dados
    ): array {
        $id = isset($dados['id'])
            ? (int) $dados['id']
            : 0;

        if ($id <= 0) {
            throw new InvalidArgumentException(
                'O ID da rubrica é obrigatório.'
            );
        }

        $rubrica = $this->model->atualizar(
            $id,
            $dados,
            $empresaId,
            $usuarioId
        );

        return [
            'sucesso' => true,
            'mensagem' => 'Rubrica salarial atualizada com sucesso.',
            'dados' => $rubrica,
        ];
    }

    public function actualizar(
        int $empresaId,
        ?int $usuarioId,
        array $dados
    ): array {
        return $this->atualizar(
            $empresaId,
            $usuarioId,
            $dados
        );
    }

    public function ativar(
        int $empresaId,
        ?int $usuarioId,
        array $dados
    ): array {
        $id = isset($dados['id'])
            ? (int) $dados['id']
            : 0;

        if ($id <= 0) {
            throw new InvalidArgumentException(
                'O ID da rubrica é obrigatório.'
            );
        }

        $rubrica = $this->model->ativar(
            $id,
            $empresaId,
            $usuarioId
        );

        return [
            'sucesso' => true,
            'mensagem' => 'Rubrica salarial ativada com sucesso.',
            'dados' => $rubrica,
        ];
    }

    public function activar(
        int $empresaId,
        ?int $usuarioId,
        array $dados
    ): array {
        return $this->ativar(
            $empresaId,
            $usuarioId,
            $dados
        );
    }

    public function inativar(
        int $empresaId,
        ?int $usuarioId,
        array $dados
    ): array {
        $id = isset($dados['id'])
            ? (int) $dados['id']
            : 0;

        if ($id <= 0) {
            throw new InvalidArgumentException(
                'O ID da rubrica é obrigatório.'
            );
        }

        $rubrica = $this->model->inativar(
            $id,
            $empresaId,
            $usuarioId
        );

        return [
            'sucesso' => true,
            'mensagem' => 'Rubrica salarial inativada com sucesso.',
            'dados' => $rubrica,
        ];
    }

    public function inactivar(
        int $empresaId,
        ?int $usuarioId,
        array $dados
    ): array {
        return $this->inativar(
            $empresaId,
            $usuarioId,
            $dados
        );
    }

    public function listarEstados(): array
    {
        return [
            'sucesso' => true,
            'dados' => $this->model->listarEstados(),
        ];
    }

    public function resumo(
        int $empresaId
    ): array {
        return [
            'sucesso' => true,
            'dados' => $this->model->resumo(
                $empresaId
            ),
        ];
    }

    public function calcular(
        int $empresaId,
        array $dados
    ): array {
        $id = isset($dados['id'])
            ? (int) $dados['id']
            : 0;

        if ($id <= 0) {
            throw new InvalidArgumentException(
                'O ID da rubrica é obrigatório.'
            );
        }

        $rubrica = $this->model->buscarPorId(
            $id,
            $empresaId
        );

        if ($rubrica === null) {
            throw new RuntimeException(
                'Rubrica salarial não encontrada.'
            );
        }

        $contexto = isset($dados['contexto'])
            && is_array($dados['contexto'])
            ? $dados['contexto']
            : $dados;

        $valor = $this->model->calcularValorRubrica(
            $rubrica,
            $contexto
        );

        return [
            'sucesso' => true,
            'dados' => [
                'rubrica_id' => (int) $rubrica['id'],
                'rubrica_uuid' => $rubrica['uuid'],
                'codigo' => $rubrica['codigo'],
                'nome' => $rubrica['nome'],
                'tipo' => $rubrica['tipo'],
                'modo_calculo' => $rubrica['modo_calculo'],
                'valor_calculado' => $valor,
            ],
        ];
    }
}
