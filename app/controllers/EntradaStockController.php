<?php

declare(strict_types=1);

require_once __DIR__
    . '/../core/Database.php';

require_once __DIR__
    . '/../models/EntradaStock.php';


final class EntradaStockController
{
    private PDO $db;

    private EntradaStock $model;


    public function __construct()
    {
        $this->db =
            Database::conn();


        $this->model =
            new EntradaStock(
                $this->db
            );
    }


    /*
    |--------------------------------------------------------------------------
    | ARMAZÉNS
    |--------------------------------------------------------------------------
    */

    public function armazens(): array
    {
        try {

            return [
                'sucesso' => true,

                'dados' =>
                $this->model
                    ->listarArmazens(
                        $this->empresaId()
                    )
            ];
        } catch (Throwable $e) {

            return $this->erro(
                $e
            );
        }
    }


    /*
    |--------------------------------------------------------------------------
    | PRODUTOS
    |--------------------------------------------------------------------------
    */

    public function produtos(): array
    {
        try {

            return [
                'sucesso' => true,

                'dados' =>
                $this->model
                    ->listarProdutos(
                        $this->empresaId()
                    )
            ];
        } catch (Throwable $e) {

            return $this->erro(
                $e
            );
        }
    }


    /*
    |--------------------------------------------------------------------------
    | STATUS
    |--------------------------------------------------------------------------
    */

    public function status(): array
    {
        try {

            return [
                'sucesso' => true,

                'dados' =>
                $this->model
                    ->listarStatus()
            ];
        } catch (Throwable $e) {

            return $this->erro(
                $e
            );
        }
    }


    /*
    |--------------------------------------------------------------------------
    | GUARDAR
    |--------------------------------------------------------------------------
    */

    public function guardar(): array
    {
        try {

            $empresaId =
                $this->empresaId();


            $usuarioId =
                $this->usuarioId();


            /*
            |--------------------------------------------------------------------------
            | ARMAZÉM
            |--------------------------------------------------------------------------
            */

            $armazemId =
                (int)(
                    $_POST['armazem_id']
                    ?? 0
                );


            if ($armazemId <= 0) {

                return [
                    'sucesso' => false,

                    'mensagem' =>
                    'Selecione o armazém de destino.'
                ];
            }


            if (
                !$this->model
                    ->armazemPertenceEmpresa(
                        $armazemId,
                        $empresaId
                    )
            ) {

                return [
                    'sucesso' => false,

                    'mensagem' =>
                    'O armazém selecionado não pertence à empresa.'
                ];
            }


            /*
            |--------------------------------------------------------------------------
            | STATUS
            |--------------------------------------------------------------------------
            */

            $statusId =
                $this->model
                ->statusConfirmado();


            if ($statusId === null) {

                return [
                    'sucesso' => false,

                    'mensagem' =>
                    'O estado Confirmado para movimentos de stock não está configurado.'
                ];
            }


            /*
            |--------------------------------------------------------------------------
            | DATA
            |--------------------------------------------------------------------------
            */

            $dataMovimento =
                trim(
                    (string)(
                        $_POST['data_movimento']
                        ?? ''
                    )
                );


            if ($dataMovimento === '') {

                return [
                    'sucesso' => false,

                    'mensagem' =>
                    'Informe a data da entrada.'
                ];
            }


            $dataMovimento =
                str_replace(
                    'T',
                    ' ',
                    $dataMovimento
                );


            if (
                strlen(
                    $dataMovimento
                ) === 16
            ) {

                $dataMovimento .=
                    ':00';
            }


            /*
            |--------------------------------------------------------------------------
            | OBSERVAÇÕES
            |--------------------------------------------------------------------------
            */

            $observacoes =
                trim(
                    (string)(
                        $_POST['observacoes']
                        ?? ''
                    )
                );


            if ($observacoes === '') {

                $observacoes =
                    'Entrada manual de stock';
            }


            /*
            |--------------------------------------------------------------------------
            | ORIGEM CONTABILÍSTICA
            |--------------------------------------------------------------------------
            */

            $origemContabilistica =
                strtoupper(
                    trim(
                        (string)(
                            $_POST['origem_contabilistica']
                            ?? ''
                        )
                    )
                );

            if (
                !in_array(
                    $origemContabilistica,
                    [
                        'STOCK_INICIAL',
                        'CAPITAL_SOCIOS',
                        'AJUSTE_POSITIVO'
                    ],
                    true
                )
            ) {
                return [
                    'sucesso' => false,
                    'mensagem' =>
                    'Selecione a origem contabilística da entrada.'
                ];
            }


            /*
            |--------------------------------------------------------------------------
            | ITENS
            |--------------------------------------------------------------------------
            */

            $itensJson =
                (string)(
                    $_POST['itens']
                    ?? ''
                );


            if ($itensJson === '') {

                return [
                    'sucesso' => false,

                    'mensagem' =>
                    'Adicione pelo menos um produto.'
                ];
            }


            $itens =
                json_decode(
                    $itensJson,
                    true
                );


            if (
                json_last_error()
                !== JSON_ERROR_NONE
            ) {

                return [
                    'sucesso' => false,

                    'mensagem' =>
                    'Os itens da entrada possuem um formato inválido.'
                ];
            }


            if (
                !is_array($itens)
                || count($itens) === 0
            ) {

                return [
                    'sucesso' => false,

                    'mensagem' =>
                    'Nenhum produto válido foi informado.'
                ];
            }


            /*
            |--------------------------------------------------------------------------
            | VALIDAR ITENS
            |--------------------------------------------------------------------------
            */

            $produtosUtilizados =
                [];


            foreach (
                $itens as $indice => &$item
            ) {

                $produtoId =
                    (int)(
                        $item['produto_id']
                        ?? 0
                    );


                $quantidade =
                    (float)(
                        $item['quantidade']
                        ?? 0
                    );


                $custoUnitario =
                    (float)(
                        $item['custo_unitario']
                        ?? 0
                    );


                if ($produtoId <= 0) {

                    return [
                        'sucesso' => false,

                        'mensagem' =>
                        'Selecione o produto na linha '
                            . ($indice + 1)
                            . '.'
                    ];
                }


                $produto =
                    $this->model
                    ->produto(
                        $produtoId,
                        $empresaId
                    );


                if (!$produto) {

                    return [
                        'sucesso' => false,

                        'mensagem' =>
                        'O produto da linha '
                            . ($indice + 1)
                            . ' é inválido.'
                    ];
                }


                if ($quantidade <= 0) {

                    return [
                        'sucesso' => false,

                        'mensagem' =>
                        'A quantidade da linha '
                            . ($indice + 1)
                            . ' deve ser superior a zero.'
                    ];
                }


                if ($custoUnitario <= 0) {

                    return [
                        'sucesso' => false,

                        'mensagem' =>
                        'O custo da linha '
                            . ($indice + 1)
                            . ' deve ser superior a zero.'
                    ];
                }


                /*
                 * Impedir o mesmo produto
                 * duas vezes na entrada.
                 */
                if (
                    isset(
                        $produtosUtilizados[$produtoId]
                    )
                ) {

                    return [
                        'sucesso' => false,

                        'mensagem' =>
                        'O produto "'
                            . $produto['nome']
                            . '" foi informado mais de uma vez.'
                    ];
                }


                $produtosUtilizados[$produtoId] = true;


                /*
                 * Mantemos também nome e SKU.
                 *
                 * O Model não precisa deles para
                 * movimento_estoque_itens, mas serão
                 * úteis para a auditoria.
                 */
                $item = [

                    'produto_id' =>
                    $produtoId,

                    'produto_nome' =>
                    (string)(
                        $produto['nome']
                        ?? ''
                    ),

                    'produto_sku' =>
                    (string)(
                        $produto['sku']
                        ?? ''
                    ),

                    'quantidade' =>
                    $quantidade,

                    'custo_unitario' =>
                    $custoUnitario
                ];
            }


            unset($item);


            /*
            |--------------------------------------------------------------------------
            | CRIAR ENTRADA
            |--------------------------------------------------------------------------
            */

            $movimentoId =
                $this->model
                ->criar(
                    $empresaId,
                    $usuarioId,
                    $armazemId,
                    $statusId,
                    $dataMovimento,
                    $observacoes,
                    $itens,
                    $origemContabilistica
                );


            return [
                'sucesso' => true,

                'mensagem' =>
                'Entrada de stock registada com sucesso.',

                'movimento_id' =>
                $movimentoId
            ];
        } catch (Throwable $e) {

            return $this->erro(
                $e
            );
        }
    }


    /*
    |--------------------------------------------------------------------------
    | EMPRESA
    |--------------------------------------------------------------------------
    */

    private function empresaId(): int
    {
        $empresaId =
            $_SESSION['empresa_id']
            ?? $_SESSION['usuario']['empresa_id']
            ?? $_SESSION['user']['empresa_id']
            ?? null;


        if (
            $empresaId === null
            || !is_numeric(
                $empresaId
            )
            || (int)$empresaId <= 0
        ) {

            throw new RuntimeException(
                'Empresa não identificada na sessão.'
            );
        }


        return (int)$empresaId;
    }


    /*
    |--------------------------------------------------------------------------
    | UTILIZADOR
    |--------------------------------------------------------------------------
    */

    private function usuarioId(): int
    {
        $usuarioId =
            $_SESSION['usuario_id']
            ?? $_SESSION['utilizador_id']
            ?? $_SESSION['usuario']['id']
            ?? $_SESSION['user']['id']
            ?? null;


        if (
            $usuarioId === null
            || !is_numeric(
                $usuarioId
            )
            || (int)$usuarioId <= 0
        ) {

            throw new RuntimeException(
                'Utilizador não identificado na sessão.'
            );
        }


        return (int)$usuarioId;
    }


    /*
    |--------------------------------------------------------------------------
    | ERRO
    |--------------------------------------------------------------------------
    */

    private function erro(
        Throwable $e
    ): array {

        error_log(
            sprintf(
                '[EntradaStockController] %s em %s:%d',
                $e->getMessage(),
                $e->getFile(),
                $e->getLine()
            )
        );


        return [
            'sucesso' => false,

            'mensagem' =>
            $e instanceof RuntimeException
                || $e instanceof InvalidArgumentException

                ? $e->getMessage()

                : 'Ocorreu um erro interno ao registar a entrada de stock.'
        ];
    }
}
