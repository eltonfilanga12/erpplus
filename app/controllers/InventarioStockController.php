<?php

declare(strict_types=1);

require_once __DIR__
    . '/../core/Database.php';

require_once __DIR__
    . '/../models/InventarioStock.php';


final class InventarioStockController
{
    private PDO $db;

    private InventarioStock $model;


    public function __construct()
    {
        $this->db =
            Database::conn();


        $this->model =
            new InventarioStock(
                $this->db
            );
    }


    public function listar(): array
    {
        try {

            return [
                'sucesso' => true,

                'dados' =>
                $this->model
                    ->listar(
                        $this->empresaId()
                    )
            ];
        } catch (Throwable $e) {

            return $this->erro($e);
        }
    }


    public function resumo(): array
    {
        try {

            return [
                'sucesso' => true,

                'dados' =>
                $this->model
                    ->resumo(
                        $this->empresaId()
                    )
            ];
        } catch (Throwable $e) {

            return $this->erro($e);
        }
    }


    public function armazens(): array
    {
        try {

            return [
                'sucesso' => true,

                'dados' =>
                $this->model
                    ->armazens(
                        $this->empresaId()
                    )
            ];
        } catch (Throwable $e) {

            return $this->erro($e);
        }
    }


    public function buscar(): array
    {
        try {

            $id =
                (int)(
                    $_GET['id']
                    ?? 0
                );


            if ($id <= 0) {

                return [
                    'sucesso' => false,
                    'mensagem' =>
                    'Inventário inválido.'
                ];
            }


            $inventario =
                $this->model
                ->buscar(
                    $id,
                    $this->empresaId()
                );


            if (!$inventario) {

                return [
                    'sucesso' => false,
                    'mensagem' =>
                    'Inventário não encontrado.'
                ];
            }


            return [
                'sucesso' => true,

                'dados' => [

                    'inventario' =>
                    $inventario,

                    'itens' =>
                    $this->model
                        ->itens(
                            $id
                        )
                ]
            ];
        } catch (Throwable $e) {

            return $this->erro($e);
        }
    }


    public function criar(): array
    {
        try {

            $empresaId =
                $this->empresaId();


            $usuarioId =
                $this->usuarioId();


            $armazemId =
                (int)(
                    $_POST['armazem_id']
                    ?? 0
                );


            if ($armazemId <= 0) {

                return [
                    'sucesso' => false,

                    'mensagem' =>
                    'Selecione o armazém.'
                ];
            }


            $data =
                trim(
                    (string)(
                        $_POST['data_abertura']
                        ?? ''
                    )
                );


            if ($data === '') {

                return [
                    'sucesso' => false,

                    'mensagem' =>
                    'Informe a data de abertura.'
                ];
            }


            $data =
                str_replace(
                    'T',
                    ' ',
                    $data
                );


            if (
                strlen($data)
                === 16
            ) {

                $data .= ':00';
            }


            $descricao =
                trim(
                    (string)(
                        $_POST['descricao']
                        ?? ''
                    )
                );


            $id =
                $this->model
                ->criar(
                    $empresaId,
                    $armazemId,
                    $usuarioId,
                    $data,
                    $descricao !== ''
                        ? $descricao
                        : null
                );


            return [
                'sucesso' => true,

                'mensagem' =>
                'Inventário aberto com sucesso.',

                'inventario_id' =>
                $id
            ];
        } catch (Throwable $e) {

            return $this->erro($e);
        }
    }


    public function guardarContagem(): array
    {
        try {

            $empresaId =
                $this->empresaId();


            $usuarioId =
                $this->usuarioId();


            $inventarioId =
                (int)(
                    $_POST['inventario_id']
                    ?? 0
                );


            if ($inventarioId <= 0) {

                return [
                    'sucesso' => false,

                    'mensagem' =>
                    'Inventário inválido.'
                ];
            }


            $inventario =
                $this->model
                ->buscar(
                    $inventarioId,
                    $empresaId
                );


            if (!$inventario) {

                return [
                    'sucesso' => false,

                    'mensagem' =>
                    'Inventário não encontrado.'
                ];
            }


            if (
                $inventario['status_nome']
                !== 'Aberto'
            ) {

                return [
                    'sucesso' => false,

                    'mensagem' =>
                    'Este inventário já não está aberto.'
                ];
            }


            $itens =
                json_decode(
                    $_POST['itens']
                        ?? '',
                    true
                );


            if (
                !is_array($itens)
                || count($itens) === 0
            ) {

                return [
                    'sucesso' => false,

                    'mensagem' =>
                    'Nenhuma contagem foi informada.'
                ];
            }


            $dados = [];


            foreach (
                $itens as $indice => $item
            ) {

                $id =
                    (int)(
                        $item['id']
                        ?? 0
                    );


                $quantidade =
                    $item['quantidade_fisica']
                    ?? null;


                if ($id <= 0) {

                    return [
                        'sucesso' => false,

                        'mensagem' =>
                        'Item inválido na linha '
                            . ($indice + 1)
                            . '.'
                    ];
                }


                if (
                    $quantidade === null
                    || $quantidade === ''
                    || !is_numeric($quantidade)
                ) {

                    return [
                        'sucesso' => false,

                        'mensagem' =>
                        'Informe a quantidade física na linha '
                            . ($indice + 1)
                            . '.'
                    ];
                }


                $quantidade =
                    (float)$quantidade;


                if ($quantidade < 0) {

                    return [
                        'sucesso' => false,

                        'mensagem' =>
                        'A quantidade física não pode ser negativa.'
                    ];
                }


                $dados[] = [

                    'id' =>
                    $id,

                    'quantidade_fisica' =>
                    $quantidade,

                    'observacoes' =>
                    trim(
                        (string)(
                            $item['observacoes']
                            ?? ''
                        )
                    )
                ];
            }


            $this->model
                ->guardarContagem(
                    $inventarioId,
                    $empresaId,
                    $usuarioId,
                    $dados
                );


            return [
                'sucesso' => true,

                'mensagem' =>
                'Contagem guardada com sucesso.'
            ];
        } catch (Throwable $e) {

            return $this->erro($e);
        }
    }


    public function finalizar(): array
    {
        try {

            $inventarioId =
                (int)(
                    $_POST['inventario_id']
                    ?? 0
                );


            if ($inventarioId <= 0) {

                return [
                    'sucesso' => false,

                    'mensagem' =>
                    'Inventário inválido.'
                ];
            }


            $movimentoId =
                $this->model
                ->finalizar(
                    $inventarioId,
                    $this->empresaId(),
                    $this->usuarioId()
                );


            return [
                'sucesso' => true,

                'mensagem' =>
                $movimentoId > 0

                    ? 'Inventário finalizado e ajuste de stock gerado com sucesso.'

                    : 'Inventário finalizado. Não foram encontradas diferenças de stock.',

                'movimento_id' =>
                $movimentoId > 0
                    ? $movimentoId
                    : null
            ];
        } catch (Throwable $e) {

            return $this->erro($e);
        }
    }


    public function cancelar(): array
    {
        try {

            $inventarioId =
                (int)(
                    $_POST['inventario_id']
                    ?? 0
                );


            if ($inventarioId <= 0) {

                return [
                    'sucesso' => false,

                    'mensagem' =>
                    'Inventário inválido.'
                ];
            }


            $this->model
                ->cancelar(
                    $inventarioId,
                    $this->empresaId(),
                    $this->usuarioId()
                );


            return [
                'sucesso' => true,

                'mensagem' =>
                'Inventário cancelado com sucesso.'
            ];
        } catch (Throwable $e) {

            return $this->erro($e);
        }
    }


    private function empresaId(): int
    {
        $empresaId =
            $_SESSION['empresa_id']
            ?? $_SESSION['usuario']['empresa_id']
            ?? $_SESSION['user']['empresa_id']
            ?? null;


        if (
            !is_numeric($empresaId)
            || (int)$empresaId <= 0
        ) {

            throw new RuntimeException(
                'Empresa não identificada na sessão.'
            );
        }


        return (int)$empresaId;
    }


    private function usuarioId(): int
    {
        $usuarioId =
            $_SESSION['usuario_id']
            ?? $_SESSION['utilizador_id']
            ?? $_SESSION['usuario']['id']
            ?? $_SESSION['user']['id']
            ?? null;


        if (
            !is_numeric($usuarioId)
            || (int)$usuarioId <= 0
        ) {

            throw new RuntimeException(
                'Utilizador não identificado na sessão.'
            );
        }


        return (int)$usuarioId;
    }


    private function erro(
        Throwable $e
    ): array {

        error_log(
            sprintf(
                '[InventarioStockController] %s em %s:%d',
                $e->getMessage(),
                $e->getFile(),
                $e->getLine()
            )
        );


        return [
            'sucesso' => false,

            'mensagem' =>
            $e->getMessage()
        ];
    }
}
