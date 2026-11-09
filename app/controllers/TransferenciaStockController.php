<?php

declare(strict_types=1);

require_once __DIR__
    . '/../core/Database.php';

require_once __DIR__
    . '/../models/TransferenciaStock.php';


final class TransferenciaStockController
{
    private PDO $db;

    private TransferenciaStock $model;


    public function __construct()
    {
        $this->db =
            Database::conn();


        $this->model =
            new TransferenciaStock(
                $this->db
            );
    }


    // ==================================================
    // ARMAZÉNS
    // ==================================================

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

            return $this->erro($e);
        }
    }


    // ==================================================
    // PRODUTOS
    // ==================================================

    public function produtos(): array
    {
        try {

            $empresaId =
                $this->empresaId();


            $armazemId =
                (int)(
                    $_GET['armazem_id']
                    ?? 0
                );


            if ($armazemId <= 0) {

                return [
                    'sucesso' => false,

                    'mensagem' =>
                    'Informe o armazém de origem.'
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
                    'Armazém de origem inválido.'
                ];
            }


            return [
                'sucesso' => true,

                'dados' =>
                $this->model
                    ->listarProdutosPorArmazem(
                        $empresaId,
                        $armazemId
                    )
            ];
        } catch (Throwable $e) {

            return $this->erro($e);
        }
    }


    // ==================================================
    // GUARDAR
    // ==================================================

    public function guardar(): array
    {
        try {

            $empresaId =
                $this->empresaId();


            $usuarioId =
                $this->usuarioId();


            // ==========================================
            // ORIGEM
            // ==========================================

            $origemId =
                (int)(
                    $_POST['armazem_origem_id']
                    ?? 0
                );


            // ==========================================
            // DESTINO
            // ==========================================

            $destinoId =
                (int)(
                    $_POST['armazem_destino_id']
                    ?? 0
                );


            if ($origemId <= 0) {

                return [
                    'sucesso' => false,

                    'mensagem' =>
                    'Selecione o armazém de origem.'
                ];
            }


            if ($destinoId <= 0) {

                return [
                    'sucesso' => false,

                    'mensagem' =>
                    'Selecione o armazém de destino.'
                ];
            }


            if ($origemId === $destinoId) {

                return [
                    'sucesso' => false,

                    'mensagem' =>
                    'O armazém de origem e o armazém de destino devem ser diferentes.'
                ];
            }


            if (
                !$this->model
                    ->armazemPertenceEmpresa(
                        $origemId,
                        $empresaId
                    )
            ) {

                return [
                    'sucesso' => false,

                    'mensagem' =>
                    'O armazém de origem é inválido.'
                ];
            }


            if (
                !$this->model
                    ->armazemPertenceEmpresa(
                        $destinoId,
                        $empresaId
                    )
            ) {

                return [
                    'sucesso' => false,

                    'mensagem' =>
                    'O armazém de destino é inválido.'
                ];
            }


            // ==========================================
            // STATUS
            // ==========================================

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


            // ==========================================
            // DATA
            // ==========================================

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
                    'Informe a data da transferência.'
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


            // ==========================================
            // OBSERVAÇÕES
            // ==========================================

            $observacoes =
                trim(
                    (string)(
                        $_POST['observacoes']
                        ?? ''
                    )
                );


            if ($observacoes === '') {

                $observacoes =
                    'Transferência manual de stock';
            }


            // ==========================================
            // ITENS
            // ==========================================

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
                    'Os itens da transferência possuem um formato inválido.'
                ];
            }


            if (
                !is_array($itens)
                || count($itens) === 0
            ) {

                return [
                    'sucesso' => false,

                    'mensagem' =>
                    'Adicione pelo menos um produto.'
                ];
            }


            // ==========================================
            // VALIDAR ITENS
            // ==========================================

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


                if ($produtoId <= 0) {

                    return [
                        'sucesso' => false,

                        'mensagem' =>
                        'Selecione o produto na linha '
                            . ($indice + 1)
                            . '.'
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
                        'Produto inválido na linha '
                            . ($indice + 1)
                            . '.'
                    ];
                }


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


                /*
                 * Validação amigável.
                 *
                 * O EstoqueService validará novamente
                 * dentro da transação.
                 */
                $saldo =
                    $this->model
                    ->saldo(
                        $empresaId,
                        $origemId,
                        $produtoId
                    );


                if (
                    $quantidade
                    > $saldo + 0.000001
                ) {

                    return [
                        'sucesso' => false,

                        'mensagem' =>
                        'Stock insuficiente para "'
                            . $produto['nome']
                            . '". Disponível: '
                            . $saldo
                            . '.'
                    ];
                }


                $produtosUtilizados[$produtoId] = true;


                /*
                 * O custo será definido pelo
                 * CMP real da origem.
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
                    0.0
                ];
            }


            unset($item);


            // ==========================================
            // TRANSFERIR
            // ==========================================

            $movimentoId =
                $this->model
                ->transferir(
                    $empresaId,
                    $usuarioId,
                    $origemId,
                    $destinoId,
                    $statusId,
                    $dataMovimento,
                    $observacoes,
                    $itens
                );


            return [
                'sucesso' => true,

                'mensagem' =>
                'Transferência de stock realizada com sucesso.',

                'movimento_id' =>
                $movimentoId
            ];
        } catch (Throwable $e) {

            return $this->erro(
                $e
            );
        }
    }


    // ==================================================
    // EMPRESA
    // ==================================================

    private function empresaId(): int
    {
        $empresaId =
            $_SESSION['empresa_id']
            ?? $_SESSION['usuario']['empresa_id']
            ?? $_SESSION['user']['empresa_id']
            ?? null;


        if (
            !is_numeric(
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


    // ==================================================
    // UTILIZADOR
    // ==================================================

    private function usuarioId(): int
    {
        $usuarioId =
            $_SESSION['usuario_id']
            ?? $_SESSION['utilizador_id']
            ?? $_SESSION['usuario']['id']
            ?? $_SESSION['user']['id']
            ?? null;


        if (
            !is_numeric(
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


    // ==================================================
    // ERRO
    // ==================================================

    private function erro(
        Throwable $e
    ): array {

        error_log(
            sprintf(
                '[TransferenciaStockController] %s em %s:%d',
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

                : 'Ocorreu um erro interno ao realizar a transferência de stock.'
        ];
    }
}
