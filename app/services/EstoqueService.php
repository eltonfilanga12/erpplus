<?php

declare(strict_types=1);

final class EstoqueService
{
    /*
    |--------------------------------------------------------------------------
    | APLICAR MOVIMENTO
    |--------------------------------------------------------------------------
    */

    public static function aplicarMovimento(
        int $movimentoId,
        PDO $pdo
    ): void {

        if ($movimentoId <= 0) {
            throw new RuntimeException(
                'Movimento de stock inválido.'
            );
        }


        /*
        |--------------------------------------------------------------------------
        | MOVIMENTO
        |--------------------------------------------------------------------------
        */

        $stmt = $pdo->prepare("
            SELECT
                id,
                empresa_id,
                tipo,
                armazem_origem_id,
                armazem_destino_id,
                status_id

            FROM movimentos_estoque

            WHERE id = :id

            FOR UPDATE
        ");

        $stmt->execute([
            ':id' => $movimentoId
        ]);

        $movimento =
            $stmt->fetch(
                PDO::FETCH_ASSOC
            );


        if (!$movimento) {
            throw new RuntimeException(
                'Movimento de stock não encontrado.'
            );
        }


        /*
        |--------------------------------------------------------------------------
        | ITENS
        |--------------------------------------------------------------------------
        */

        $stmt = $pdo->prepare("
            SELECT
                id,
                produto_id,
                quantidade,
                custo_unitario

            FROM movimento_estoque_itens

            WHERE movimento_id = :movimento_id

            ORDER BY id ASC
        ");

        $stmt->execute([
            ':movimento_id' =>
            $movimentoId
        ]);

        $itens =
            $stmt->fetchAll(
                PDO::FETCH_ASSOC
            );


        if (!$itens) {
            throw new RuntimeException(
                'O movimento não possui produtos.'
            );
        }


        $empresaId =
            (int)$movimento['empresa_id'];

        $tipo =
            strtoupper(
                trim(
                    (string)$movimento['tipo']
                )
            );


        foreach ($itens as $item) {

            $produtoId =
                (int)$item['produto_id'];

            $quantidade =
                round(
                    (float)$item['quantidade'],
                    3
                );

            $custoMovimento =
                round(
                    (float)(
                        $item['custo_unitario']
                        ?? 0
                    ),
                    6
                );


            if ($produtoId <= 0) {
                throw new RuntimeException(
                    'Produto inválido no movimento de stock.'
                );
            }


            if ($quantidade <= 0) {
                throw new RuntimeException(
                    'Quantidade inválida no movimento de stock.'
                );
            }


            switch ($tipo) {

                /*
                |--------------------------------------------------------------------------
                | ENTRADA
                |--------------------------------------------------------------------------
                */

                case 'ENTRADA':

                    $armazemDestinoId =
                        (int)(
                            $movimento['armazem_destino_id']
                            ?? 0
                        );


                    if ($armazemDestinoId <= 0) {
                        throw new RuntimeException(
                            'Armazém de destino inválido.'
                        );
                    }


                    self::aplicarEntrada(
                        $pdo,
                        $empresaId,
                        $armazemDestinoId,
                        $produtoId,
                        $quantidade,
                        $custoMovimento
                    );

                    break;


                /*
                |--------------------------------------------------------------------------
                | SAÍDA
                |--------------------------------------------------------------------------
                */

                case 'SAIDA':

                    $armazemOrigemId =
                        (int)(
                            $movimento['armazem_origem_id']
                            ?? 0
                        );


                    if ($armazemOrigemId <= 0) {
                        throw new RuntimeException(
                            'Armazém de origem inválido.'
                        );
                    }


                    /*
                     * A saída utiliza obrigatoriamente
                     * o custo médio existente no armazém.
                     */

                    $custoMedio =
                        self::aplicarSaida(
                            $pdo,
                            $empresaId,
                            $armazemOrigemId,
                            $produtoId,
                            $quantidade
                        );


                    /*
                     * Grava no movimento o custo real
                     * utilizado na saída.
                     */

                    self::atualizarCustoItemMovimento(
                        $pdo,
                        (int)$item['id'],
                        $custoMedio
                    );

                    break;


                /*
                |--------------------------------------------------------------------------
                | TRANSFERÊNCIA
                |--------------------------------------------------------------------------
                */

                case 'TRANSFERENCIA':

                    $armazemOrigemId =
                        (int)(
                            $movimento['armazem_origem_id']
                            ?? 0
                        );

                    $armazemDestinoId =
                        (int)(
                            $movimento['armazem_destino_id']
                            ?? 0
                        );


                    if (
                        $armazemOrigemId <= 0
                        || $armazemDestinoId <= 0
                    ) {
                        throw new RuntimeException(
                            'Armazéns da transferência inválidos.'
                        );
                    }


                    if (
                        $armazemOrigemId
                        === $armazemDestinoId
                    ) {
                        throw new RuntimeException(
                            'O armazém de origem deve ser diferente do destino.'
                        );
                    }


                    /*
                     * Retira da origem usando o CMP da origem.
                     */

                    $custoTransferencia =
                        self::aplicarSaida(
                            $pdo,
                            $empresaId,
                            $armazemOrigemId,
                            $produtoId,
                            $quantidade
                        );


                    /*
                     * O mesmo custo acompanha a mercadoria
                     * até ao armazém destino.
                     */

                    self::aplicarEntrada(
                        $pdo,
                        $empresaId,
                        $armazemDestinoId,
                        $produtoId,
                        $quantidade,
                        $custoTransferencia
                    );


                    self::atualizarCustoItemMovimento(
                        $pdo,
                        (int)$item['id'],
                        $custoTransferencia
                    );

                    break;


                /*
                |--------------------------------------------------------------------------
                | AJUSTE
                |--------------------------------------------------------------------------
                */

                case 'AJUSTE':

                    self::aplicarAjuste(
                        $pdo,
                        $movimento,
                        $item
                    );

                    break;


                default:

                    throw new RuntimeException(
                        'Tipo de movimento de stock inválido: '
                            . $tipo
                    );
            }
        }
    }


    /*
    |--------------------------------------------------------------------------
    | ENTRADA COM CUSTO MÉDIO
    |--------------------------------------------------------------------------
    */

    private static function aplicarEntrada(
        PDO $pdo,
        int $empresaId,
        int $armazemId,
        int $produtoId,
        float $quantidadeEntrada,
        float $custoEntrada
    ): float {

        if ($custoEntrada < 0) {
            throw new RuntimeException(
                'Custo da entrada inválido.'
            );
        }


        $saldo =
            self::buscarSaldoParaAtualizacao(
                $pdo,
                $empresaId,
                $armazemId,
                $produtoId
            );


        $quantidadeAnterior =
            (float)(
                $saldo['quantidade']
                ?? 0
            );

        $custoMedioAnterior =
            (float)(
                $saldo['custo_medio']
                ?? 0
            );


        /*
         * Caso seja o primeiro stock e o movimento
         * não tenha custo, podemos utilizar o custo
         * de referência do produto.
         */

        if (
            $custoEntrada <= 0
            && $quantidadeAnterior <= 0
        ) {

            $custoEntrada =
                self::custoProduto(
                    $pdo,
                    $empresaId,
                    $produtoId
                );
        }


        $quantidadeNova =
            $quantidadeAnterior
            + $quantidadeEntrada;


        if ($quantidadeNova <= 0) {
            throw new RuntimeException(
                'Quantidade final inválida na entrada.'
            );
        }


        /*
         * CMP:
         *
         * (Q anterior × CMP anterior)
         * +
         * (Q entrada × custo entrada)
         * --------------------------------
         *       nova quantidade
         */

        if ($quantidadeAnterior > 0) {

            $valorAnterior =
                $quantidadeAnterior
                * $custoMedioAnterior;

            $valorEntrada =
                $quantidadeEntrada
                * $custoEntrada;

            $novoCustoMedio =
                (
                    $valorAnterior
                    + $valorEntrada
                )
                / $quantidadeNova;
        } else {

            /*
             * Primeiro stock daquele produto/armazém.
             */

            $novoCustoMedio =
                $custoEntrada;
        }


        $novoCustoMedio =
            round(
                $novoCustoMedio,
                6
            );


        self::guardarSaldo(
            $pdo,
            $empresaId,
            $armazemId,
            $produtoId,
            $quantidadeNova,
            $novoCustoMedio
        );


        return $novoCustoMedio;
    }


    /*
    |--------------------------------------------------------------------------
    | SAÍDA
    |--------------------------------------------------------------------------
    */

    private static function aplicarSaida(
        PDO $pdo,
        int $empresaId,
        int $armazemId,
        int $produtoId,
        float $quantidadeSaida
    ): float {

        $saldo =
            self::buscarSaldoParaAtualizacao(
                $pdo,
                $empresaId,
                $armazemId,
                $produtoId
            );


        if (!$saldo) {
            throw new RuntimeException(
                'Não existe stock para o produto informado.'
            );
        }


        $quantidadeAtual =
            (float)$saldo['quantidade'];

        $custoMedio =
            (float)$saldo['custo_medio'];


        if (
            $quantidadeAtual
            + 0.000001
            < $quantidadeSaida
        ) {
            throw new RuntimeException(
                'Saldo insuficiente para o produto.'
            );
        }


        $novaQuantidade =
            $quantidadeAtual
            - $quantidadeSaida;


        if (
            abs($novaQuantidade)
            < 0.000001
        ) {
            $novaQuantidade = 0;
        }


        /*
         * Na saída NÃO recalculamos CMP.
         */

        self::guardarSaldo(
            $pdo,
            $empresaId,
            $armazemId,
            $produtoId,
            $novaQuantidade,
            $custoMedio
        );


        return $custoMedio;
    }


    /*
    |--------------------------------------------------------------------------
    | AJUSTE
    |--------------------------------------------------------------------------
    */

    private static function aplicarAjuste(
        PDO $pdo,
        array $movimento,
        array $item
    ): void {

        $empresaId =
            (int)$movimento['empresa_id'];

        $produtoId =
            (int)$item['produto_id'];

        $quantidade =
            (float)$item['quantidade'];

        $custo =
            (float)(
                $item['custo_unitario']
                ?? 0
            );


        /*
         * Alguns ajustes utilizam origem,
         * outros destino.
         */

        $armazemId =
            !empty($movimento['armazem_destino_id'])
            ? (int)$movimento['armazem_destino_id']
            : (int)(
                $movimento['armazem_origem_id']
                ?? 0
            );


        if ($armazemId <= 0) {
            throw new RuntimeException(
                'Armazém inválido no ajuste.'
            );
        }


        /*
         * Quantidade positiva:
         * entrada por ajuste.
         */

        if ($quantidade > 0) {

            self::aplicarEntrada(
                $pdo,
                $empresaId,
                $armazemId,
                $produtoId,
                $quantidade,
                $custo
            );

            return;
        }


        /*
         * Quantidade negativa:
         * saída por ajuste.
         */

        if ($quantidade < 0) {

            self::aplicarSaida(
                $pdo,
                $empresaId,
                $armazemId,
                $produtoId,
                abs($quantidade)
            );
        }
    }


    /*
    |--------------------------------------------------------------------------
    | BUSCAR SALDO COM BLOQUEIO
    |--------------------------------------------------------------------------
    */

    private static function buscarSaldoParaAtualizacao(
        PDO $pdo,
        int $empresaId,
        int $armazemId,
        int $produtoId
    ): ?array {

        $stmt =
            $pdo->prepare("
                SELECT
                    quantidade,
                    custo_medio

                FROM estoque_saldos

                WHERE empresa_id =
                    :empresa_id

                  AND armazem_id =
                    :armazem_id

                  AND produto_id =
                    :produto_id

                FOR UPDATE
            ");


        $stmt->execute([
            ':empresa_id' =>
            $empresaId,

            ':armazem_id' =>
            $armazemId,

            ':produto_id' =>
            $produtoId
        ]);


        $saldo =
            $stmt->fetch(
                PDO::FETCH_ASSOC
            );


        return $saldo ?: null;
    }


    /*
    |--------------------------------------------------------------------------
    | GUARDAR SALDO
    |--------------------------------------------------------------------------
    */

    private static function guardarSaldo(
        PDO $pdo,
        int $empresaId,
        int $armazemId,
        int $produtoId,
        float $quantidade,
        float $custoMedio
    ): void {

        $stmt =
            $pdo->prepare("
                INSERT INTO estoque_saldos
                (
                    empresa_id,
                    armazem_id,
                    produto_id,
                    quantidade,
                    custo_medio
                )

                VALUES
                (
                    :empresa_id,
                    :armazem_id,
                    :produto_id,
                    :quantidade,
                    :custo_medio
                )

                ON DUPLICATE KEY UPDATE

                    quantidade =
                        VALUES(quantidade),

                    custo_medio =
                        VALUES(custo_medio),

                    atualizado_em =
                        CURRENT_TIMESTAMP
            ");


        $stmt->execute([
            ':empresa_id' =>
            $empresaId,

            ':armazem_id' =>
            $armazemId,

            ':produto_id' =>
            $produtoId,

            ':quantidade' =>
            round(
                $quantidade,
                3
            ),

            ':custo_medio' =>
            round(
                $custoMedio,
                6
            )
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | CUSTO DE REFERÊNCIA DO PRODUTO
    |--------------------------------------------------------------------------
    */

    private static function custoProduto(
        PDO $pdo,
        int $empresaId,
        int $produtoId
    ): float {

        $stmt =
            $pdo->prepare("
                SELECT preco_custo

                FROM produtos

                WHERE id =
                    :produto_id

                  AND empresa_id =
                    :empresa_id

                LIMIT 1
            ");


        $stmt->execute([
            ':produto_id' =>
            $produtoId,

            ':empresa_id' =>
            $empresaId
        ]);


        $custo =
            $stmt->fetchColumn();


        if ($custo === false) {
            throw new RuntimeException(
                'Produto não encontrado.'
            );
        }


        return round(
            (float)$custo,
            6
        );
    }


    /*
    |--------------------------------------------------------------------------
    | ATUALIZAR CUSTO DO ITEM DO MOVIMENTO
    |--------------------------------------------------------------------------
    */

    private static function atualizarCustoItemMovimento(
        PDO $pdo,
        int $itemId,
        float $custoUnitario
    ): void {

        $stmt =
            $pdo->prepare("
                UPDATE movimento_estoque_itens

                SET custo_unitario =
                    :custo_unitario

                WHERE id =
                    :id
            ");


        $stmt->execute([
            ':custo_unitario' =>
            round(
                $custoUnitario,
                6
            ),

            ':id' =>
            $itemId
        ]);
    }
}
