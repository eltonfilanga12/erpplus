<?php

declare(strict_types=1);

final class PermissaoController
{
    private Permissao $model;

    public function __construct()
    {
        $this->model =
            new Permissao();
    }

    public function perfis(): array
    {
        try {
            return [
                'sucesso' => true,
                'dados' =>
                $this->model
                    ->listarPerfis()
            ];
        } catch (Throwable $e) {
            return $this->erro($e);
        }
    }

    public function carregar(): array
    {
        try {
            $perfilId =
                $this->inteiroObrigatorio(
                    $_GET['perfil_id']
                        ?? $_POST['perfil_id']
                        ?? null,
                    'Selecione um perfil.'
                );

            if (
                !$this->model->perfilExiste(
                    $perfilId
                )
            ) {
                return [
                    'sucesso' => false,
                    'mensagem' =>
                    'O perfil selecionado não existe.'
                ];
            }

            return [
                'sucesso' => true,
                'dados' =>
                $this->model
                    ->listarPorPerfil(
                        $perfilId
                    )
            ];
        } catch (Throwable $e) {
            return $this->erro($e);
        }
    }

    public function guardar(
        array $entrada
    ): array {
        try {
            $perfilId =
                $this->inteiroObrigatorio(
                    $entrada['perfil_id']
                        ?? null,
                    'Selecione um perfil.'
                );

            if (
                !$this->model->perfilExiste(
                    $perfilId
                )
            ) {
                return [
                    'sucesso' => false,
                    'mensagem' =>
                    'O perfil selecionado não existe.'
                ];
            }

            $permissoes =
                $entrada['permissoes']
                ?? [];

            if (!is_array($permissoes)) {
                throw new InvalidArgumentException(
                    'A lista de permissões é inválida.'
                );
            }

            $this->model->salvar(
                $perfilId,
                $permissoes
            );

            return [
                'sucesso' => true,
                'mensagem' =>
                'Permissões guardadas com sucesso.'
            ];
        } catch (Throwable $e) {
            return $this->erro($e);
        }
    }

    private function inteiroObrigatorio(
        mixed $valor,
        string $mensagem
    ): int {
        $numero =
            filter_var(
                $valor,
                FILTER_VALIDATE_INT
            );

        if (
            $numero === false
            || $numero <= 0
        ) {
            throw new InvalidArgumentException(
                $mensagem
            );
        }

        return (int) $numero;
    }

    private function erro(
        Throwable $e
    ): array {
        error_log(
            sprintf(
                '[PermissaoController] %s em %s:%d',
                $e->getMessage(),
                $e->getFile(),
                $e->getLine()
            )
        );

        return [
            'sucesso' => false,
            'mensagem' =>
            $e instanceof InvalidArgumentException
                ? $e->getMessage()
                : 'Ocorreu um erro ao processar as permissões.'
        ];
    }
}
