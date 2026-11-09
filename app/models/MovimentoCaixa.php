<?php

declare(strict_types=1);

final class MovimentoCaixa
{
    private PDO $pdo;


    public function __construct(
        PDO $pdo
    ) {
        $this->pdo = $pdo;
    }


    // ==================================================
    // LISTAR
    // ==================================================

    public function listar(
        int $empresaId,
        array $filtros = []
    ): array {

        if ($empresaId <= 0) {
            throw new RuntimeException(
                'Empresa inválida.'
            );
        }


        $sql = "
            SELECT
                movimento.id,
                movimento.empresa_id,
                movimento.filial_id,
                movimento.centro_custo_id,
                movimento.caixa_id,
                movimento.abertura_id,
                movimento.usuario_id,
                movimento.tipo,
                movimento.origem,
                movimento.documento_id,
                movimento.descricao,
                movimento.valor,
                movimento.data_movimento,
                movimento.criado_em,

                caixa.nome AS caixa,

                filial.nome AS filial,

                centro.nome AS centro_custo,
                centro.codigo AS centro_custo_codigo,

                usuario.nome AS operador

            FROM movimentos_caixa movimento

            INNER JOIN caixas caixa
                ON caixa.id =
                    movimento.caixa_id

            LEFT JOIN filiais filial
                ON filial.id =
                    movimento.filial_id

            LEFT JOIN centros_custo centro
                ON centro.id =
                    movimento.centro_custo_id

            LEFT JOIN usuarios usuario
                ON usuario.id =
                    movimento.usuario_id

            WHERE movimento.empresa_id =
                :empresa_id
        ";


        $params = [
            ':empresa_id' =>
            $empresaId
        ];


        if (
            !empty($filtros['caixa_id'])
        ) {

            $sql .= "
                AND movimento.caixa_id =
                    :caixa_id
            ";

            $params[':caixa_id'] =
                (int)$filtros['caixa_id'];
        }


        if (
            !empty($filtros['filial_id'])
        ) {

            $sql .= "
                AND movimento.filial_id =
                    :filial_id
            ";

            $params[':filial_id'] =
                (int)$filtros['filial_id'];
        }


        if (
            !empty($filtros['centro_custo_id'])
        ) {

            $sql .= "
                AND movimento.centro_custo_id =
                    :centro_custo_id
            ";

            $params[':centro_custo_id'] =
                (int)$filtros['centro_custo_id'];
        }


        if (
            !empty($filtros['tipo'])
        ) {

            $sql .= "
                AND movimento.tipo =
                    :tipo
            ";

            $params[':tipo'] =
                strtoupper(
                    trim(
                        (string)$filtros['tipo']
                    )
                );
        }


        if (
            !empty($filtros['origem'])
        ) {

            $sql .= "
                AND movimento.origem =
                    :origem
            ";

            $params[':origem'] =
                strtoupper(
                    trim(
                        (string)$filtros['origem']
                    )
                );
        }


        if (
            !empty($filtros['data_inicio'])
        ) {

            $sql .= "
                AND DATE(
                    movimento.data_movimento
                ) >= :data_inicio
            ";

            $params[':data_inicio'] =
                $filtros['data_inicio'];
        }


        if (
            !empty($filtros['data_fim'])
        ) {

            $sql .= "
                AND DATE(
                    movimento.data_movimento
                ) <= :data_fim
            ";

            $params[':data_fim'] =
                $filtros['data_fim'];
        }


        $sql .= "
            ORDER BY
                movimento.data_movimento DESC,
                movimento.id DESC
        ";


        $stmt =
            $this->pdo->prepare(
                $sql
            );


        $stmt->execute(
            $params
        );


        return $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );
    }


    // ==================================================
    // BUSCAR
    // ==================================================

    public function buscar(
        int $id,
        int $empresaId
    ): ?array {

        if (
            $id <= 0
            || $empresaId <= 0
        ) {

            throw new RuntimeException(
                'Movimento de caixa inválido.'
            );
        }


        $stmt =
            $this->pdo->prepare("
                SELECT
                    movimento.*,

                    caixa.nome AS caixa,

                    filial.nome AS filial,

                    centro.nome AS centro_custo,
                    centro.codigo AS centro_custo_codigo,

                    usuario.nome AS operador

                FROM movimentos_caixa movimento

                INNER JOIN caixas caixa
                    ON caixa.id =
                        movimento.caixa_id

                LEFT JOIN filiais filial
                    ON filial.id =
                        movimento.filial_id

                LEFT JOIN centros_custo centro
                    ON centro.id =
                        movimento.centro_custo_id

                LEFT JOIN usuarios usuario
                    ON usuario.id =
                        movimento.usuario_id

                WHERE movimento.id =
                    :id

                  AND movimento.empresa_id =
                    :empresa_id

                LIMIT 1
            ");


        $stmt->execute([
            ':id' =>
            $id,

            ':empresa_id' =>
            $empresaId
        ]);


        return $stmt->fetch(
            PDO::FETCH_ASSOC
        ) ?: null;
    }


    // ==================================================
    // REGISTAR MOVIMENTO MANUAL
    // ==================================================

    public function registrar(
        array $dados
    ): int {

        $empresaId =
            (int)(
                $dados['empresa_id']
                ?? 0
            );


        $caixaId =
            (int)(
                $dados['caixa_id']
                ?? 0
            );


        $usuarioId =
            (int)(
                $dados['usuario_id']
                ?? 0
            );


        $tipo =
            strtoupper(
                trim(
                    (string)(
                        $dados['tipo']
                        ?? ''
                    )
                )
            );


        $origem =
            strtoupper(
                trim(
                    (string)(
                        $dados['origem']
                        ?? 'MANUAL'
                    )
                )
            );


        $valor =
            round(
                (float)(
                    $dados['valor']
                    ?? 0
                ),
                2
            );


        $this->validarMovimento(
            $empresaId,
            $caixaId,
            $usuarioId,
            $tipo,
            $origem,
            $valor
        );


        $this->pdo
            ->beginTransaction();


        try {

            $caixa =
                $this->buscarCaixaAbertoBloqueado(
                    $caixaId,
                    $empresaId
                );


            $aberturaId =
                (int)$caixa['abertura_id'];


            $saldoAtual =
                (float)$caixa['saldo_atual'];


            if (
                in_array(
                    $tipo,
                    [
                        'SAIDA',
                        'SANGRIA'
                    ],
                    true
                )
                &&
                $saldoAtual < $valor
            ) {

                throw new RuntimeException(
                    'Saldo insuficiente no caixa.'
                );
            }


            $movimentoId =
                $this->inserirMovimento([
                    'empresa_id' =>
                    $empresaId,

                    'filial_id' =>
                    $dados['filial_id']
                        ?? $caixa['filial_id']
                        ?? null,

                    'centro_custo_id' =>
                    $dados['centro_custo_id']
                        ?? null,

                    'caixa_id' =>
                    $caixaId,

                    'abertura_id' =>
                    $aberturaId,

                    'usuario_id' =>
                    $usuarioId,

                    'tipo' =>
                    $tipo,

                    'origem' =>
                    $origem,

                    'documento_id' =>
                    $dados['documento_id']
                        ?? null,

                    'descricao' =>
                    $dados['descricao']
                        ?? null,

                    'valor' =>
                    $valor,

                    'data_movimento' =>
                    $dados['data_movimento']
                        ?? null
                ]);

            $this->atualizarSaldoCaixa(
                $caixaId,
                $empresaId,
                $tipo,
                $valor
            );


            $this->pdo
                ->commit();


            return $movimentoId;
        } catch (
            Throwable $e
        ) {

            if (
                $this->pdo
                ->inTransaction()
            ) {

                $this->pdo
                    ->rollBack();
            }


            throw $e;
        }
    }

    private function atualizarSaldoCaixa(
        int $caixaId,
        int $empresaId,
        string $tipo,
        float $valor
    ): void {

        $tipo =
            strtoupper(
                trim($tipo)
            );

        if (
            in_array(
                $tipo,
                [
                    'ENTRADA',
                    'SUPRIMENTO'
                ],
                true
            )
        ) {

            $operacao = '+';
        } elseif (
            in_array(
                $tipo,
                [
                    'SAIDA',
                    'SANGRIA'
                ],
                true
            )
        ) {

            $operacao = '-';
        } else {

            throw new RuntimeException(
                'Tipo inválido para atualização do saldo do caixa.'
            );
        }


        $sql = "
        UPDATE caixas
        SET saldo_atual =
            saldo_atual {$operacao} :valor

        WHERE id =
            :caixa_id

          AND empresa_id =
            :empresa_id
    ";


        $stmt =
            $this->pdo->prepare(
                $sql
            );


        $stmt->execute([
            ':valor' =>
            $valor,

            ':caixa_id' =>
            $caixaId,

            ':empresa_id' =>
            $empresaId
        ]);


        if (
            $stmt->rowCount() !== 1
        ) {

            throw new RuntimeException(
                'Não foi possível atualizar o saldo do caixa.'
            );
        }
    }

    // ==================================================
    // TRANSFERÊNCIA ENTRE CAIXAS
    // ==================================================

    public function transferir(
        array $dados
    ): array {

        $empresaId =
            (int)(
                $dados['empresa_id']
                ?? 0
            );


        $usuarioId =
            (int)(
                $dados['usuario_id']
                ?? 0
            );


        $caixaOrigemId =
            (int)(
                $dados['caixa_origem_id']
                ?? 0
            );


        $caixaDestinoId =
            (int)(
                $dados['caixa_destino_id']
                ?? 0
            );


        $valor =
            round(
                (float)(
                    $dados['valor']
                    ?? 0
                ),
                2
            );


        if ($empresaId <= 0) {
            throw new RuntimeException(
                'Empresa inválida.'
            );
        }


        if ($usuarioId <= 0) {
            throw new RuntimeException(
                'Utilizador inválido.'
            );
        }


        if (
            $caixaOrigemId <= 0
            || $caixaDestinoId <= 0
        ) {

            throw new RuntimeException(
                'Selecione o caixa de origem e destino.'
            );
        }


        if (
            $caixaOrigemId ===
            $caixaDestinoId
        ) {

            throw new RuntimeException(
                'O caixa de origem e destino devem ser diferentes.'
            );
        }


        if ($valor <= 0) {

            throw new RuntimeException(
                'O valor da transferência deve ser superior a zero.'
            );
        }


        $this->pdo
            ->beginTransaction();


        try {

            /*
             * Bloqueia sempre na mesma ordem.
             */

            $ids = [
                $caixaOrigemId,
                $caixaDestinoId
            ];


            sort(
                $ids
            );


            foreach (
                $ids
                as $caixaId
            ) {

                $this->buscarCaixaAbertoBloqueado(
                    $caixaId,
                    $empresaId
                );
            }


            $origem =
                $this->buscarCaixaAbertoBloqueado(
                    $caixaOrigemId,
                    $empresaId
                );


            $destino =
                $this->buscarCaixaAbertoBloqueado(
                    $caixaDestinoId,
                    $empresaId
                );


            $saldoOrigem =
                (float)$origem['saldo_atual'];

            if (
                $saldoOrigem <
                $valor
            ) {

                throw new RuntimeException(
                    'Saldo insuficiente no caixa de origem.'
                );
            }


            $data =
                $this->normalizarData(
                    $dados['data_movimento']
                        ??
                        $dados['data_transferencia']
                        ??
                        null
                );


            $descricao =
                $this->textoOuNull(
                    $dados['descricao']
                        ?? null
                )
                ??
                'Transferência entre caixas';


            /*
             * SAÍDA
             */

            $movimentoSaidaId =
                $this->inserirMovimento([

                    'empresa_id' =>
                    $empresaId,

                    'filial_id' =>
                    $dados['filial_id']
                        ??
                        $origem['filial_id']
                        ??
                        null,

                    'centro_custo_id' =>
                    $dados['centro_custo_id']
                        ?? null,

                    'caixa_id' =>
                    $caixaOrigemId,

                    'abertura_id' =>
                    (int)$origem['abertura_id'],

                    'usuario_id' =>
                    $usuarioId,

                    'tipo' =>
                    'SAIDA',

                    'origem' =>
                    'TRANSFERENCIA',

                    'documento_id' =>
                    null,

                    'descricao' =>
                    $descricao
                        . ' para '
                        . $destino['nome'],

                    'valor' =>
                    $valor,

                    'data_movimento' =>
                    $data
                ]);

            $this->atualizarSaldoCaixa(
                $caixaOrigemId,
                $empresaId,
                'SAIDA',
                $valor
            );

            /*
             * ENTRADA
             *
             * Na tua tabela, TRANSFERENCIA é um tipo próprio.
             * Para distinguir entrada/saída no saldo, precisamos
             * tratar pelo movimento relacionado à origem/destino.
             *
             * Por isso, nesta implementação usamos ENTRADA no destino
             * e origem TRANSFERENCIA.
             */

            $movimentoEntradaId =
                $this->inserirMovimento([

                    'empresa_id' =>
                    $empresaId,

                    'filial_id' =>
                    $dados['filial_id']
                        ??
                        $destino['filial_id']
                        ??
                        null,

                    'centro_custo_id' =>
                    $dados['centro_custo_id']
                        ?? null,

                    'caixa_id' =>
                    $caixaDestinoId,

                    'abertura_id' =>
                    (int)$destino['abertura_id'],

                    'usuario_id' =>
                    $usuarioId,

                    'tipo' =>
                    'ENTRADA',

                    'origem' =>
                    'TRANSFERENCIA',

                    'documento_id' =>
                    $movimentoSaidaId,

                    'descricao' =>
                    $descricao
                        . ' recebida de '
                        . $origem['nome'],

                    'valor' =>
                    $valor,

                    'data_movimento' =>
                    $data
                ]);

            $this->atualizarSaldoCaixa(
                $caixaDestinoId,
                $empresaId,
                'ENTRADA',
                $valor
            );


            $this->pdo
                ->commit();


            return [

                'movimento_saida_id' =>
                $movimentoSaidaId,

                'movimento_entrada_id' =>
                $movimentoEntradaId,

                'valor' =>
                $valor,

                'caixa_origem_id' =>
                $caixaOrigemId,

                'caixa_destino_id' =>
                $caixaDestinoId
            ];
        } catch (
            Throwable $e
        ) {

            if (
                $this->pdo
                ->inTransaction()
            ) {

                $this->pdo
                    ->rollBack();
            }


            throw $e;
        }
    }

    // ==================================================
    // EXTRATO
    // ==================================================

    public function extrato(
        int $caixaId,
        int $empresaId,
        array $filtros = []
    ): array {

        $filtros['caixa_id'] =
            $caixaId;


        return $this->listar(
            $empresaId,
            $filtros
        );
    }


    // ==================================================
    // SALDO ATUAL
    // ==================================================

    public function saldoAtual(
        int $caixaId,
        int $empresaId
    ): float {

        $stmt =
            $this->pdo->prepare("
            SELECT saldo_atual

            FROM caixas

            WHERE id =
                :caixa_id

              AND empresa_id =
                :empresa_id

            LIMIT 1
        ");

        $stmt->execute([
            ':caixa_id' =>
            $caixaId,

            ':empresa_id' =>
            $empresaId
        ]);

        $saldo =
            $stmt->fetchColumn();

        if ($saldo === false) {
            throw new RuntimeException(
                'Caixa não encontrado.'
            );
        }

        return (float)$saldo;
    }

    // ==================================================
    // LISTAR CAIXAS ABERTOS
    // ==================================================

    public function listarCaixasAbertos(
        int $empresaId
    ): array {

        if ($empresaId <= 0) {
            throw new RuntimeException(
                'Empresa inválida.'
            );
        }

        $stmt =
            $this->pdo->prepare("
            SELECT
                caixa.id,
                caixa.nome,
                caixa.filial_id,
                caixa.saldo_atual,

                filial.nome AS filial,

                abertura.id AS abertura_id,
                abertura.data_abertura

            FROM caixas caixa

            INNER JOIN status status_caixa
                ON status_caixa.id =
                    caixa.status_id

            INNER JOIN abertura_caixa abertura
                ON abertura.id = (
                    SELECT ac.id
                    FROM abertura_caixa ac
                    WHERE ac.caixa_id =
                        caixa.id
                      AND ac.empresa_id =
                        caixa.empresa_id
                      AND ac.data_fechamento IS NULL
                    ORDER BY ac.id DESC
                    LIMIT 1
                )

            LEFT JOIN filiais filial
                ON filial.id =
                    caixa.filial_id

            WHERE caixa.empresa_id =
                :empresa_id

              AND UPPER(
                    status_caixa.nome
                  ) = 'ABERTO'

            ORDER BY
                caixa.nome ASC
        ");

        $stmt->execute([
            ':empresa_id' =>
            $empresaId
        ]);

        return $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );
    }

    // ==================================================
    // INSERIR MOVIMENTO
    // ==================================================

    private function inserirMovimento(
        array $dados
    ): int {

        $dataMovimento =
            $this->normalizarData(
                $dados['data_movimento']
                    ?? null
            );


        $stmt =
            $this->pdo->prepare("
                INSERT INTO movimentos_caixa
                (
                    empresa_id,
                    filial_id,
                    centro_custo_id,
                    caixa_id,
                    abertura_id,
                    usuario_id,
                    tipo,
                    origem,
                    documento_id,
                    descricao,
                    valor,
                    data_movimento
                )
                VALUES
                (
                    :empresa_id,
                    :filial_id,
                    :centro_custo_id,
                    :caixa_id,
                    :abertura_id,
                    :usuario_id,
                    :tipo,
                    :origem,
                    :documento_id,
                    :descricao,
                    :valor,
                    :data_movimento
                )
            ");


        $stmt->execute([

            ':empresa_id' =>
            (int)$dados['empresa_id'],

            ':filial_id' =>
            !empty($dados['filial_id'])
                ? (int)$dados['filial_id']
                : null,

            ':centro_custo_id' =>
            !empty($dados['centro_custo_id'])
                ? (int)$dados['centro_custo_id']
                : null,

            ':caixa_id' =>
            (int)$dados['caixa_id'],

            ':abertura_id' =>
            (int)$dados['abertura_id'],

            ':usuario_id' =>
            (int)$dados['usuario_id'],

            ':tipo' =>
            strtoupper(
                trim(
                    (string)$dados['tipo']
                )
            ),

            ':origem' =>
            strtoupper(
                trim(
                    (string)(
                        $dados['origem']
                        ?? 'MANUAL'
                    )
                )
            ),

            ':documento_id' =>
            !empty($dados['documento_id'])
                ? (int)$dados['documento_id']
                : null,

            ':descricao' =>
            $this->textoOuNull(
                $dados['descricao']
                    ?? null
            ),

            ':valor' =>
            round(
                (float)$dados['valor'],
                2
            ),

            ':data_movimento' =>
            $dataMovimento
        ]);


        return (int)$this->pdo
            ->lastInsertId();
    }


    // ==================================================
    // BUSCAR CAIXA ABERTO
    // ==================================================

    private function buscarCaixaAberto(
        int $caixaId,
        int $empresaId
    ): ?array {

        $stmt =
            $this->pdo->prepare("
            SELECT
                caixa.id,
                caixa.nome,
                caixa.filial_id,
                caixa.status_id,
                caixa.saldo_atual,

                abertura.id AS abertura_id,
                abertura.data_abertura

            FROM caixas caixa

            INNER JOIN status status_caixa
                ON status_caixa.id =
                    caixa.status_id

            LEFT JOIN abertura_caixa abertura
                ON abertura.id = (
                    SELECT ac.id
                    FROM abertura_caixa ac
                    WHERE ac.caixa_id =
                        caixa.id
                      AND ac.empresa_id =
                        caixa.empresa_id
                      AND ac.data_fechamento IS NULL
                    ORDER BY ac.id DESC
                    LIMIT 1
                )

            WHERE caixa.id =
                :caixa_id

              AND caixa.empresa_id =
                :empresa_id

              AND UPPER(
                    status_caixa.nome
                  ) = 'ABERTO'

            LIMIT 1
        ");

        $stmt->execute([
            ':caixa_id' =>
            $caixaId,

            ':empresa_id' =>
            $empresaId
        ]);

        $caixa =
            $stmt->fetch(
                PDO::FETCH_ASSOC
            );

        if (
            !$caixa
            || empty($caixa['abertura_id'])
        ) {
            return null;
        }

        return $caixa;
    }


    // ==================================================
    // BUSCAR CAIXA ABERTO COM LOCK
    // ==================================================

    private function buscarCaixaAbertoBloqueado(
        int $caixaId,
        int $empresaId
    ): array {

        $stmt =
            $this->pdo->prepare("
            SELECT
                caixa.id,
                caixa.nome,
                caixa.filial_id,
                caixa.status_id,
                caixa.saldo_atual,

                (
                    SELECT ac.id
                    FROM abertura_caixa ac
                    WHERE ac.caixa_id =
                        caixa.id
                      AND ac.empresa_id =
                        caixa.empresa_id
                      AND ac.data_fechamento IS NULL
                    ORDER BY ac.id DESC
                    LIMIT 1
                ) AS abertura_id

            FROM caixas caixa

            INNER JOIN status status_caixa
                ON status_caixa.id =
                    caixa.status_id

            WHERE caixa.id =
                :caixa_id

              AND caixa.empresa_id =
                :empresa_id

              AND UPPER(
                    status_caixa.nome
                  ) = 'ABERTO'

            FOR UPDATE
        ");

        $stmt->execute([
            ':caixa_id' =>
            $caixaId,

            ':empresa_id' =>
            $empresaId
        ]);

        $caixa =
            $stmt->fetch(
                PDO::FETCH_ASSOC
            );

        if (!$caixa) {
            throw new RuntimeException(
                'Caixa não encontrado ou não está aberto.'
            );
        }

        if (
            empty($caixa['abertura_id'])
        ) {
            throw new RuntimeException(
                'O caixa não possui uma abertura activa.'
            );
        }

        return $caixa;
    }


    // ==================================================
    // VALIDAR MOVIMENTO
    // ==================================================

    private function validarMovimento(
        int $empresaId,
        int $caixaId,
        int $usuarioId,
        string $tipo,
        string $origem,
        float $valor
    ): void {

        if ($empresaId <= 0) {
            throw new RuntimeException(
                'Empresa inválida.'
            );
        }


        if ($caixaId <= 0) {
            throw new RuntimeException(
                'Caixa inválido.'
            );
        }


        if ($usuarioId <= 0) {
            throw new RuntimeException(
                'Utilizador inválido.'
            );
        }


        $tiposPermitidos = [
            'ENTRADA',
            'SAIDA',
            'SANGRIA',
            'SUPRIMENTO',
            'TRANSFERENCIA'
        ];


        if (
            !in_array(
                $tipo,
                $tiposPermitidos,
                true
            )
        ) {

            throw new RuntimeException(
                'Tipo de movimento de caixa inválido.'
            );
        }


        $origensPermitidas = [
            'VENDA',
            'COMPRA',
            'RECEBIMENTO',
            'PAGAMENTO',
            'TRANSFERENCIA',
            'MANUAL'
        ];


        if (
            !in_array(
                $origem,
                $origensPermitidas,
                true
            )
        ) {

            throw new RuntimeException(
                'Origem do movimento de caixa inválida.'
            );
        }


        if ($valor <= 0) {

            throw new RuntimeException(
                'O valor do movimento deve ser superior a zero.'
            );
        }
    }


    // ==================================================
    // DATA
    // ==================================================

    private function normalizarData(
        mixed $valor
    ): string {

        if (
            $valor === null
            ||
            trim(
                (string)$valor
            ) === ''
        ) {

            return date(
                'Y-m-d H:i:s'
            );
        }


        $valor =
            trim(
                (string)$valor
            );


        $valor =
            str_replace(
                'T',
                ' ',
                $valor
            );


        if (
            strlen($valor)
            === 16
        ) {

            $valor .= ':00';
        }


        $data =
            DateTimeImmutable
            ::createFromFormat(
                'Y-m-d H:i:s',
                $valor
            );


        if (!$data) {

            throw new RuntimeException(
                'Data do movimento inválida.'
            );
        }


        return $data->format(
            'Y-m-d H:i:s'
        );
    }


    // ==================================================
    // TEXTO OU NULL
    // ==================================================

    private function textoOuNull(
        mixed $valor
    ): ?string {

        $valor =
            trim(
                (string)(
                    $valor
                    ?? ''
                )
            );


        return $valor !== ''
            ? $valor
            : null;
    }
}
