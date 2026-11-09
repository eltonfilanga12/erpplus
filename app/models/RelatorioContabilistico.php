<?php

declare(strict_types=1);

final class RelatorioContabilistico
{
    private PDO $db;


    public function __construct(PDO $db)
    {
        $this->db = $db;
    }


    /*
    |--------------------------------------------------------------------------
    | STATUS CONTABILIZADO
    |--------------------------------------------------------------------------
    */

    private function statusContabilizadoId(): int
    {
        $stmt =
            $this->db->prepare("
                SELECT id

                FROM status

                WHERE tipo =
                    'lancamento_contabilistico'

                  AND LOWER(nome) =
                    'contabilizado'

                LIMIT 1
            ");


        $stmt->execute();


        $id =
            (int)$stmt->fetchColumn();


        if ($id <= 0) {

            throw new RuntimeException(
                'Status Contabilizado não encontrado.'
            );
        }


        return $id;
    }


    /*
    |--------------------------------------------------------------------------
    | DIÁRIO
    |--------------------------------------------------------------------------
    */

    public function diario(
        int $empresaId,
        array $filtros
    ): array {

        $statusId =
            $this->statusContabilizadoId();


        $where = [
            'l.empresa_id = :empresa_id',
            'l.status_id = :status_id'
        ];


        $params = [
            ':empresa_id' =>
            $empresaId,

            ':status_id' =>
            $statusId
        ];


        $this->aplicarFiltrosLancamento(
            $where,
            $params,
            $filtros,
            'l'
        );


        if (
            !empty($filtros['conta_id'])
        ) {

            $where[] =
                'i.conta_contabil_id = :conta_id';

            $params[':conta_id'] =
                (int)$filtros['conta_id'];
        }


        if (
            !empty($filtros['pesquisa'])
        ) {

            $where[] = "
                (
                    l.numero_documento
                        LIKE :pesquisa

                    OR l.referencia
                        LIKE :pesquisa

                    OR l.descricao
                        LIKE :pesquisa

                    OR i.descricao
                        LIKE :pesquisa

                    OR c.codigo
                        LIKE :pesquisa

                    OR c.nome
                        LIKE :pesquisa
                )
            ";


            $params[':pesquisa'] =
                '%'
                . $filtros['pesquisa']
                . '%';
        }


        $sql = "
            SELECT

                l.id AS lancamento_id,

                l.numero,

                l.numero_documento,

                l.referencia,

                l.data_lancamento,

                l.descricao
                    AS lancamento_descricao,

                l.origem,

                d.codigo
                    AS diario_codigo,

                d.nome
                    AS diario_nome,

                p.nome
                    AS periodo_nome,

                c.codigo
                    AS conta_codigo,

                c.nome
                    AS conta_nome,

                c.natureza
                    AS conta_natureza,

                i.descricao
                    AS item_descricao,

                i.debito,

                i.credito,

                i.ordem,

                COALESCE(
                    f.nome,
                    'Sem filial'
                ) AS filial,

                COALESCE(
                    cc.nome,
                    '-'
                ) AS centro_custo,

                COALESCE(
                    cli.nome,
                    forn.nome,
                    '-'
                ) AS entidade

            FROM lancamentos_contabilisticos l

            INNER JOIN
                lancamento_contabilistico_itens i
                ON i.lancamento_id = l.id

            INNER JOIN
                contas_contabilisticas c
                ON c.id = i.conta_contabil_id
               AND c.empresa_id = l.empresa_id

            INNER JOIN
                diarios_contabilisticos d
                ON d.id = l.diario_id
               AND d.empresa_id = l.empresa_id

            INNER JOIN
                periodos_contabilisticos p
                ON p.id = l.periodo_id
               AND p.empresa_id = l.empresa_id

            LEFT JOIN filiais f
                ON f.id = l.filial_id
               AND f.empresa_id = l.empresa_id

            LEFT JOIN centros_custo cc
                ON cc.id = i.centro_custo_id
               AND cc.empresa_id = l.empresa_id

            LEFT JOIN clientes cli
                ON cli.id = i.cliente_id
               AND cli.empresa_id = l.empresa_id

            LEFT JOIN fornecedores forn
                ON forn.id = i.fornecedor_id
               AND forn.empresa_id = l.empresa_id

            WHERE "
            . implode(
                ' AND ',
                $where
            )
            . "

            ORDER BY
                l.data_lancamento ASC,
                d.codigo ASC,
                l.numero ASC,
                i.ordem ASC
        ";


        $stmt =
            $this->db->prepare($sql);


        $stmt->execute($params);


        $registos =
            $stmt->fetchAll(
                PDO::FETCH_ASSOC
            );


        $debito = 0.0;
        $credito = 0.0;
        $lancamentos = [];


        foreach ($registos as $registo) {

            $debito +=
                (float)$registo['debito'];

            $credito +=
                (float)$registo['credito'];

            $lancamentos[(int)$registo['lancamento_id']] = true;
        }


        return [

            'resumo' => [

                'total_lancamentos' =>
                count($lancamentos),

                'total_linhas' =>
                count($registos),

                'total_debito' =>
                $debito,

                'total_credito' =>
                $credito,

                'diferenca' =>
                $debito - $credito
            ],

            'registos' =>
            $registos
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | RAZÃO
    |--------------------------------------------------------------------------
    */

    public function razao(
        int $empresaId,
        array $filtros
    ): array {

        $contaId =
            (int)(
                $filtros['conta_id']
                ?? 0
            );


        if ($contaId <= 0) {

            throw new InvalidArgumentException(
                'Selecione uma conta contabilística para gerar o Razão.'
            );
        }


        $conta =
            $this->buscarConta(
                $empresaId,
                $contaId
            );


        if (!$conta) {

            throw new RuntimeException(
                'Conta contabilística não encontrada.'
            );
        }


        $statusId =
            $this->statusContabilizadoId();


        /*
        |--------------------------------------------------------------------------
        | Saldo inicial
        |--------------------------------------------------------------------------
        */

        $saldoInicialDebito = 0.0;
        $saldoInicialCredito = 0.0;


        if (
            !empty($filtros['data_inicio'])
        ) {

            $whereInicial = [
                'l.empresa_id = :empresa_id',
                'l.status_id = :status_id',
                'i.conta_contabil_id = :conta_id',
                'l.data_lancamento < :data_inicio'
            ];


            $paramsInicial = [
                ':empresa_id' =>
                $empresaId,

                ':status_id' =>
                $statusId,

                ':conta_id' =>
                $contaId,

                ':data_inicio' =>
                $filtros['data_inicio']
            ];


            if (
                !empty($filtros['filial_id'])
            ) {

                $whereInicial[] =
                    'l.filial_id = :filial_id';

                $paramsInicial[':filial_id'] =
                    (int)$filtros['filial_id'];
            }


            $stmt =
                $this->db->prepare(
                    "
                    SELECT

                        COALESCE(
                            SUM(i.debito),
                            0
                        ) AS debito,

                        COALESCE(
                            SUM(i.credito),
                            0
                        ) AS credito

                    FROM
                        lancamento_contabilistico_itens i

                    INNER JOIN
                        lancamentos_contabilisticos l
                        ON l.id = i.lancamento_id

                    WHERE "
                        . implode(
                            ' AND ',
                            $whereInicial
                        )
                );


            $stmt->execute(
                $paramsInicial
            );


            $inicial =
                $stmt->fetch(
                    PDO::FETCH_ASSOC
                ) ?: [];


            $movimentoInicial =
                (float)(
                    $inicial['debito']
                    ?? 0
                )
                -
                (float)(
                    $inicial['credito']
                    ?? 0
                );


            if ($movimentoInicial >= 0) {

                $saldoInicialDebito =
                    $movimentoInicial;
            } else {

                $saldoInicialCredito =
                    abs(
                        $movimentoInicial
                    );
            }
        }


        /*
        |--------------------------------------------------------------------------
        | Movimentos
        |--------------------------------------------------------------------------
        */

        $where = [
            'l.empresa_id = :empresa_id',
            'l.status_id = :status_id',
            'i.conta_contabil_id = :conta_id'
        ];


        $params = [
            ':empresa_id' =>
            $empresaId,

            ':status_id' =>
            $statusId,

            ':conta_id' =>
            $contaId
        ];


        $this->aplicarFiltrosLancamento(
            $where,
            $params,
            $filtros,
            'l',
            false
        );


        if (
            !empty($filtros['pesquisa'])
        ) {

            $where[] = "
                (
                    l.numero_documento
                        LIKE :pesquisa

                    OR l.referencia
                        LIKE :pesquisa

                    OR l.descricao
                        LIKE :pesquisa

                    OR i.descricao
                        LIKE :pesquisa
                )
            ";


            $params[':pesquisa'] =
                '%'
                . $filtros['pesquisa']
                . '%';
        }


        $sql = "
            SELECT

                l.id AS lancamento_id,

                l.numero,

                l.numero_documento,

                l.referencia,

                l.data_lancamento,

                l.origem,

                d.codigo
                    AS diario_codigo,

                d.nome
                    AS diario_nome,

                COALESCE(
                    i.descricao,
                    l.descricao
                ) AS descricao,

                i.debito,

                i.credito,

                COALESCE(
                    cc.nome,
                    '-'
                ) AS centro_custo,

                COALESCE(
                    cli.nome,
                    forn.nome,
                    '-'
                ) AS entidade

            FROM
                lancamento_contabilistico_itens i

            INNER JOIN
                lancamentos_contabilisticos l
                ON l.id = i.lancamento_id

            INNER JOIN
                diarios_contabilisticos d
                ON d.id = l.diario_id

            LEFT JOIN centros_custo cc
                ON cc.id = i.centro_custo_id

            LEFT JOIN clientes cli
                ON cli.id = i.cliente_id

            LEFT JOIN fornecedores forn
                ON forn.id = i.fornecedor_id

            WHERE "
            . implode(
                ' AND ',
                $where
            )
            . "

            ORDER BY
                l.data_lancamento ASC,
                l.numero ASC,
                i.ordem ASC
        ";


        $stmt =
            $this->db->prepare($sql);


        $stmt->execute($params);


        $registos =
            $stmt->fetchAll(
                PDO::FETCH_ASSOC
            );


        /*
        |--------------------------------------------------------------------------
        | Saldo acumulado
        |--------------------------------------------------------------------------
        */

        $saldo =
            $saldoInicialDebito
            - $saldoInicialCredito;


        $totalDebitos = 0.0;
        $totalCreditos = 0.0;


        foreach ($registos as &$registo) {

            $debito =
                (float)$registo['debito'];

            $credito =
                (float)$registo['credito'];


            $totalDebitos += $debito;
            $totalCreditos += $credito;


            $saldo +=
                $debito - $credito;


            $registo['saldo_devedor'] =
                $saldo >= 0
                ? $saldo
                : 0;


            $registo['saldo_credor'] =
                $saldo < 0
                ? abs($saldo)
                : 0;
        }

        unset($registo);


        return [

            'conta' =>
            $conta,

            'resumo' => [

                'saldo_inicial_devedor' =>
                $saldoInicialDebito,

                'saldo_inicial_credor' =>
                $saldoInicialCredito,

                'total_debitos' =>
                $totalDebitos,

                'total_creditos' =>
                $totalCreditos,

                'saldo_final_devedor' =>
                $saldo >= 0
                    ? $saldo
                    : 0,

                'saldo_final_credor' =>
                $saldo < 0
                    ? abs($saldo)
                    : 0
            ],

            'registos' =>
            $registos
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | BALANCETE
    |--------------------------------------------------------------------------
    */

    public function balancete(
        int $empresaId,
        array $filtros
    ): array {

        $statusId =
            $this->statusContabilizadoId();


        $where = [
            'l.empresa_id = :empresa_lancamento',
            'l.status_id = :status_id',
            'c.empresa_id = :empresa_conta',
            "c.tipo = 'ANALITICA'",
            'c.aceita_lancamento = 1'
        ];


        $params = [
            ':empresa_lancamento' =>
            $empresaId,

            ':empresa_conta' =>
            $empresaId,

            ':status_id' =>
            $statusId
        ];


        /*
    |--------------------------------------------------------------------------
    | Data inicial
    |--------------------------------------------------------------------------
    */

        if (!empty($filtros['data_inicio'])) {

            $where[] =
                'l.data_lancamento >= :data_inicio';

            $params[':data_inicio'] =
                $filtros['data_inicio'];
        }


        /*
    |--------------------------------------------------------------------------
    | Data final
    |--------------------------------------------------------------------------
    */

        if (!empty($filtros['data_fim'])) {

            $where[] =
                'l.data_lancamento <= :data_fim';

            $params[':data_fim'] =
                $filtros['data_fim'];
        }


        /*
    |--------------------------------------------------------------------------
    | Período
    |--------------------------------------------------------------------------
    */

        if (!empty($filtros['periodo_id'])) {

            $where[] =
                'l.periodo_id = :periodo_id';

            $params[':periodo_id'] =
                (int)$filtros['periodo_id'];
        }


        /*
    |--------------------------------------------------------------------------
    | Diário
    |--------------------------------------------------------------------------
    */

        if (!empty($filtros['diario_id'])) {

            $where[] =
                'l.diario_id = :diario_id';

            $params[':diario_id'] =
                (int)$filtros['diario_id'];
        }


        /*
    |--------------------------------------------------------------------------
    | Filial
    |--------------------------------------------------------------------------
    */

        if (!empty($filtros['filial_id'])) {

            $where[] =
                'l.filial_id = :filial_id';

            $params[':filial_id'] =
                (int)$filtros['filial_id'];
        }


        /*
    |--------------------------------------------------------------------------
    | Conta
    |--------------------------------------------------------------------------
    */

        if (!empty($filtros['conta_id'])) {

            $where[] =
                'c.id = :conta_id';

            $params[':conta_id'] =
                (int)$filtros['conta_id'];
        }


        /*
    |--------------------------------------------------------------------------
    | Pesquisa
    |--------------------------------------------------------------------------
    */

        if (!empty($filtros['pesquisa'])) {

            $where[] = "
            (
                c.codigo LIKE :pesquisa_codigo
                OR c.nome LIKE :pesquisa_nome
            )
        ";

            $pesquisa =
                '%'
                . $filtros['pesquisa']
                . '%';


            $params[':pesquisa_codigo'] =
                $pesquisa;

            $params[':pesquisa_nome'] =
                $pesquisa;
        }


        /*
    |--------------------------------------------------------------------------
    | SQL
    |--------------------------------------------------------------------------
    */

        $sql = "
        SELECT

            c.id,

            c.codigo,

            c.nome,

            c.natureza,

            c.grupo,

            COALESCE(
                SUM(i.debito),
                0
            ) AS debito,

            COALESCE(
                SUM(i.credito),
                0
            ) AS credito

        FROM contas_contabilisticas c

        INNER JOIN
            lancamento_contabilistico_itens i
            ON i.conta_contabil_id = c.id

        INNER JOIN
            lancamentos_contabilisticos l
            ON l.id = i.lancamento_id

        WHERE "
            . implode(
                ' AND ',
                $where
            )
            . "

        GROUP BY
            c.id,
            c.codigo,
            c.nome,
            c.natureza,
            c.grupo

        HAVING
            SUM(i.debito) <> 0
            OR SUM(i.credito) <> 0

        ORDER BY
            c.codigo ASC
    ";


        $stmt =
            $this->db->prepare($sql);


        $stmt->execute($params);


        $registos =
            $stmt->fetchAll(
                PDO::FETCH_ASSOC
            );


        /*
    |--------------------------------------------------------------------------
    | Resumo
    |--------------------------------------------------------------------------
    */

        $totalDebito = 0.0;
        $totalCredito = 0.0;

        $totalSaldoDevedor = 0.0;
        $totalSaldoCredor = 0.0;


        foreach ($registos as &$registo) {

            $debito =
                (float)$registo['debito'];

            $credito =
                (float)$registo['credito'];


            $saldo =
                $debito - $credito;


            $saldoDevedor =
                $saldo > 0
                ? $saldo
                : 0;


            $saldoCredor =
                $saldo < 0
                ? abs($saldo)
                : 0;


            $registo['saldo_devedor'] =
                $saldoDevedor;

            $registo['saldo_credor'] =
                $saldoCredor;


            $totalDebito +=
                $debito;

            $totalCredito +=
                $credito;

            $totalSaldoDevedor +=
                $saldoDevedor;

            $totalSaldoCredor +=
                $saldoCredor;
        }

        unset($registo);


        return [

            'resumo' => [

                'total_contas' =>
                count($registos),

                'total_debito' =>
                $totalDebito,

                'total_credito' =>
                $totalCredito,

                'saldo_devedor' =>
                $totalSaldoDevedor,

                'saldo_credor' =>
                $totalSaldoCredor,

                'diferenca' =>
                $totalDebito
                    - $totalCredito
            ],

            'registos' =>
            $registos
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | DRE
    |--------------------------------------------------------------------------
    */

    public function dre(
        int $empresaId,
        array $filtros
    ): array {

        $statusId =
            $this->statusContabilizadoId();


        $where = [
            'l.empresa_id = :empresa_lancamento',
            'c.empresa_id = :empresa_conta',
            'l.status_id = :status_id',
            "l.origem <> 'ENCERRAMENTO'",
            "c.grupo IN (
            'RENDIMENTO',
            'GASTO'
        )"
        ];


        $params = [
            ':empresa_lancamento' =>
            $empresaId,

            ':empresa_conta' =>
            $empresaId,

            ':status_id' =>
            $statusId
        ];


        /*
    |--------------------------------------------------------------------------
    | Data inicial
    |--------------------------------------------------------------------------
    */

        if (!empty($filtros['data_inicio'])) {

            $where[] =
                'l.data_lancamento >= :data_inicio';

            $params[':data_inicio'] =
                $filtros['data_inicio'];
        }


        /*
    |--------------------------------------------------------------------------
    | Data final
    |--------------------------------------------------------------------------
    */

        if (!empty($filtros['data_fim'])) {

            $where[] =
                'l.data_lancamento <= :data_fim';

            $params[':data_fim'] =
                $filtros['data_fim'];
        }


        /*
    |--------------------------------------------------------------------------
    | Período
    |--------------------------------------------------------------------------
    */

        if (!empty($filtros['periodo_id'])) {

            $where[] =
                'l.periodo_id = :periodo_id';

            $params[':periodo_id'] =
                (int)$filtros['periodo_id'];
        }


        /*
    |--------------------------------------------------------------------------
    | Filial
    |--------------------------------------------------------------------------
    */

        if (!empty($filtros['filial_id'])) {

            $where[] =
                'l.filial_id = :filial_id';

            $params[':filial_id'] =
                (int)$filtros['filial_id'];
        }


        /*
    |--------------------------------------------------------------------------
    | SQL
    |--------------------------------------------------------------------------
    */

        $sql = "
        SELECT

            c.id,

            c.codigo,

            c.nome,

            c.grupo,

            c.natureza,

            COALESCE(
                SUM(i.debito),
                0
            ) AS debito,

            COALESCE(
                SUM(i.credito),
                0
            ) AS credito

        FROM
            lancamento_contabilistico_itens i

        INNER JOIN
            lancamentos_contabilisticos l
            ON l.id = i.lancamento_id

        INNER JOIN
            contas_contabilisticas c
            ON c.id = i.conta_contabil_id

        WHERE "
            . implode(
                ' AND ',
                $where
            )
            . "

        GROUP BY
            c.id,
            c.codigo,
            c.nome,
            c.grupo,
            c.natureza

        HAVING
            SUM(i.debito) <> 0
            OR SUM(i.credito) <> 0

        ORDER BY
            CASE
                WHEN c.grupo = 'RENDIMENTO'
                THEN 1

                WHEN c.grupo = 'GASTO'
                THEN 2

                ELSE 3
            END,

            c.codigo ASC
    ";


        $stmt =
            $this->db->prepare($sql);


        $stmt->execute($params);


        $linhas =
            $stmt->fetchAll(
                PDO::FETCH_ASSOC
            );


        $rendimentos = [];
        $gastos = [];


        $totalRendimentos = 0.0;
        $totalGastos = 0.0;


        foreach ($linhas as $linha) {

            if (
                strtoupper(
                    (string)$linha['grupo']
                ) === 'RENDIMENTO'
            ) {

                $valor =
                    (float)$linha['credito']
                    -
                    (float)$linha['debito'];


                $linha['valor'] =
                    $valor;


                $totalRendimentos +=
                    $valor;


                $rendimentos[] =
                    $linha;
            } else {

                $valor =
                    (float)$linha['debito']
                    -
                    (float)$linha['credito'];


                $linha['valor'] =
                    $valor;


                $totalGastos +=
                    $valor;


                $gastos[] =
                    $linha;
            }
        }


        $resultado =
            $totalRendimentos
            - $totalGastos;


        $margem =
            $totalRendimentos > 0
            ? (
                $resultado
                / $totalRendimentos
            ) * 100
            : 0;


        return [

            'resumo' => [

                'total_rendimentos' =>
                $totalRendimentos,

                'total_gastos' =>
                $totalGastos,

                'resultado_liquido' =>
                $resultado,

                'margem' =>
                round(
                    $margem,
                    2
                )
            ],

            'rendimentos' =>
            $rendimentos,

            'gastos' =>
            $gastos,

            'registos' =>
            array_merge(
                $rendimentos,
                $gastos
            )
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | BALANÇO PATRIMONIAL
    |--------------------------------------------------------------------------
    */
    public function balancoPatrimonial(
        int $empresaId,
        array $filtros
    ): array {

        $statusId =
            $this->statusContabilizadoId();


        $dataReferencia =
            trim(
                (string)(
                    $filtros['data_fim']
                    ?? ''
                )
            );


        $where = [ 
            'l.empresa_id = :empresa_lancamento',
            'c.empresa_id = :empresa_conta',
            'l.status_id = :status_id',
            'l.anulado_em IS NULL',

            "c.grupo IN (
            'ATIVO',
            'PASSIVO'
        )"
        ];


        $params = [
            ':empresa_lancamento' =>
            $empresaId,

            ':empresa_conta' =>
            $empresaId,

            ':status_id' =>
            $statusId
        ];


        /*
    |--------------------------------------------------------------------------
    | Data de referência
    |--------------------------------------------------------------------------
    */

        if ($dataReferencia !== '') {

            $where[] =
                'l.data_lancamento <= :data_referencia';

            $params[':data_referencia'] =
                $dataReferencia;
        }


        /*
    |--------------------------------------------------------------------------
    | Filial
    |--------------------------------------------------------------------------
    */

        if (!empty($filtros['filial_id'])) {

            $where[] =
                'l.filial_id = :filial_id';

            $params[':filial_id'] =
                (int)$filtros['filial_id'];
        }


        /*
    |--------------------------------------------------------------------------
    | SQL
    |--------------------------------------------------------------------------
    */

        $sql = "
        SELECT

            c.id,

            c.codigo,

            c.nome,

            c.grupo,

            c.natureza,

            c.classificacao_patrimonial,

            COALESCE(
                SUM(i.debito),
                0
            ) AS debito,

            COALESCE(
                SUM(i.credito),
                0
            ) AS credito

        FROM
            lancamento_contabilistico_itens i

        INNER JOIN
            lancamentos_contabilisticos l
            ON l.id = i.lancamento_id

        INNER JOIN
            contas_contabilisticas c
            ON c.id = i.conta_contabil_id

        WHERE "
            . implode(
                ' AND ',
                $where
            )
            . "

        GROUP BY
            c.id,
            c.codigo,
            c.nome,
            c.grupo,
            c.natureza,
            c.classificacao_patrimonial

        HAVING
            SUM(i.debito) <> 0
            OR SUM(i.credito) <> 0

        ORDER BY
            CASE

                WHEN c.grupo = 'ATIVO'
                THEN 1

                WHEN c.grupo = 'PASSIVO'
                THEN 2

                WHEN c.grupo = 'CAPITAL_PROPRIO'
                THEN 3

                ELSE 4

            END,

            c.codigo ASC
    ";


        $stmt =
            $this->db->prepare($sql);


        $stmt->execute($params);


        $linhas =
            $stmt->fetchAll(
                PDO::FETCH_ASSOC
            );


        /*
    |--------------------------------------------------------------------------
    | Separar grupos
    |--------------------------------------------------------------------------
    */

        $ativos = [];
        $passivos = [];
        $totalAtivo = 0.0;
        $totalPassivo = 0.0;


        foreach ($linhas as $linha) {

            $grupo =
                strtoupper(
                    trim(
                        (string)$linha['grupo']
                    )
                );


            $natureza =
                strtoupper(
                    trim(
                        (string)(
                            $linha['natureza']
                            ?? ''
                        )
                    )
                );


            $debito =
                (float)$linha['debito'];

            $credito =
                (float)$linha['credito'];


            /*
    |--------------------------------------------------------------------------
    | Saldo contabilístico bruto
    |--------------------------------------------------------------------------
    */

            $saldoBruto =
                $debito - $credito;


            $linha['saldo_contrario'] =
                false;


            $linha['alerta'] =
                null;


            /*
    |--------------------------------------------------------------------------
    | ATIVO
    |--------------------------------------------------------------------------
    */

            if ($grupo === 'ATIVO') {

                $valor =
                    $saldoBruto;


                /*
        |--------------------------------------------------------------------------
        | Uma conta de Ativo normalmente é devedora.
        | Se terminar credora, mantemos o valor negativo
        | e apenas sinalizamos.
        |--------------------------------------------------------------------------
        */

                if ($valor < 0) {

                    $linha['saldo_contrario'] =
                        true;


                    $linha['alerta'] =
                        'Saldo credor numa conta de ativo.';
                }


                if (
                    abs($valor) < 0.005
                ) {
                    continue;
                }


                $linha['valor'] =
                    $valor;


                $totalAtivo +=
                    $valor;


                $ativos[] =
                    $linha;


                continue;
            }


            /*
    |--------------------------------------------------------------------------
    | PASSIVO
    |--------------------------------------------------------------------------
    */

            if ($grupo === 'PASSIVO') {

                /*
        | Passivo normalmente é credor.
        */

                $valor =
                    $credito - $debito;


                if ($valor < 0) {

                    $linha['saldo_contrario'] =
                        true;


                    $linha['alerta'] =
                        'Saldo devedor numa conta de passivo.';
                }


                if (
                    abs($valor) < 0.005
                ) {
                    continue;
                }


                $linha['valor'] =
                    $valor;


                $totalPassivo +=
                    $valor;


                $passivos[] =
                    $linha;


                continue;
            }
        }

        /*
    |--------------------------------------------------------------------------
    | Capital próprio apurado pela equação patrimonial
    |--------------------------------------------------------------------------
    */

        $capitalProprio =
            $totalAtivo
            - $totalPassivo;


        $resultadosAnteriores =
            $this->resultadosMensaisAnteriores(
                $empresaId,
                $dataReferencia,

                !empty($filtros['filial_id'])
                    ? (int)$filtros['filial_id']
                    : null
            );


        /*
    |--------------------------------------------------------------------------
    | Conferência
    |--------------------------------------------------------------------------
    */

        $passivoCapital =
            $totalPassivo
            + $capitalProprio;


        $totalAlertas =
            count(
                array_filter(
                    array_merge(
                        $ativos,
                        $passivos
                    ),
                    static fn(array $linha): bool =>
                    !empty($linha['saldo_contrario'])
                )
            );

        return [

            'resumo' => [

                'total_alertas' =>
                $totalAlertas,

                'total_ativo' =>
                $totalAtivo,

                'total_passivo' =>
                $totalPassivo,

                'capital_proprio' =>
                $capitalProprio,

                'resultado_periodo' =>
                0,

                'capital_resultado' =>
                $capitalProprio,

                'passivo_capital' =>
                $passivoCapital,

                'diferenca' =>
                $totalAtivo
                    - $passivoCapital
            ],

            'ativos' =>
            $ativos,

            'passivos' =>
            $passivos,

            'capital_proprio' =>
            [],

            'resultados_anteriores' =>
            $resultadosAnteriores,

            'registos' =>
            array_merge(
                $ativos,
                $passivos
            )
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | RESULTADOS MENSAIS ANTERIORES À DATA DO BALANÇO
    | São valores informativos/provisórios. Não movimentam a conta 5.9.
    |--------------------------------------------------------------------------
    */

    private function resultadosMensaisAnteriores(
        int $empresaId,
        string $dataReferencia,
        ?int $filialId
    ): array {

        if ($dataReferencia === '') {
            return [];
        }

        $referencia =
            DateTimeImmutable::createFromFormat(
                '!Y-m-d',
                $dataReferencia
            );

        if ($referencia === false) {
            return [];
        }

        $inicioExercicio =
            $referencia->setDate(
                (int)$referencia->format('Y'),
                1,
                1
            );

        $fimMesAnterior =
            $referencia
            ->modify('first day of this month')
            ->modify('-1 day');

        if ($fimMesAnterior < $inicioExercicio) {
            return [];
        }

        $statusId =
            $this->statusContabilizadoId();

        $where = [
            'l.empresa_id = :empresa_id',
            'l.status_id = :status_id',
            "l.origem <> 'ENCERRAMENTO'",
            "c.grupo IN ('RENDIMENTO', 'GASTO')",
            'l.data_lancamento BETWEEN :data_inicio AND :data_fim'
        ];

        $params = [
            ':empresa_id' => $empresaId,
            ':status_id' => $statusId,
            ':data_inicio' => $inicioExercicio->format('Y-m-d'),
            ':data_fim' => $fimMesAnterior->format('Y-m-d')
        ];

        if ($filialId !== null && $filialId > 0) {
            $where[] = 'l.filial_id = :filial_id';
            $params[':filial_id'] = $filialId;
        }

        $sql = "
            SELECT
                DATE_FORMAT(l.data_lancamento, '%Y-%m') AS periodo,
                COALESCE(SUM(
                    CASE
                        WHEN c.grupo = 'RENDIMENTO'
                        THEN i.credito - i.debito
                        ELSE 0
                    END
                ), 0) AS rendimentos,
                COALESCE(SUM(
                    CASE
                        WHEN c.grupo = 'GASTO'
                        THEN i.debito - i.credito
                        ELSE 0
                    END
                ), 0) AS gastos

            FROM lancamento_contabilistico_itens i

            INNER JOIN lancamentos_contabilisticos l
                ON l.id = i.lancamento_id

            INNER JOIN contas_contabilisticas c
                ON c.id = i.conta_contabil_id
               AND c.empresa_id = l.empresa_id

            WHERE " . implode(' AND ', $where) . "

            GROUP BY DATE_FORMAT(l.data_lancamento, '%Y-%m')
            ORDER BY periodo ASC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        $resultados = [];

        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $linha) {
            $rendimentos = (float)$linha['rendimentos'];
            $gastos = (float)$linha['gastos'];

            $resultados[] = [
                'periodo' => (string)$linha['periodo'],
                'rendimentos' => $rendimentos,
                'gastos' => $gastos,
                'resultado' => $rendimentos - $gastos,
                'situacao' => 'PROVISORIO'
            ];
        }

        return $resultados;
    }

    /*
    |--------------------------------------------------------------------------
    | IVA
    |--------------------------------------------------------------------------
    */

    public function iva(
        int $empresaId,
        array $filtros
    ): array {

        $statusId =
            $this->statusContabilizadoId();


        $where = [
            'l.empresa_id = :empresa_id',
            'l.status_id = :status_id',
            "COALESCE(l.origem, '') NOT IN ('APURAMENTO_IVA', 'PAGAMENTO_IVA')",
            "COALESCE(l.numero_documento, '') NOT LIKE 'IVA-AP-%'",
            "COALESCE(l.numero_documento, '') NOT LIKE 'IVA-PG-%'"
        ];


        $params = [
            ':empresa_id' =>
            $empresaId,

            ':status_id' =>
            $statusId
        ];


        $this->aplicarFiltrosLancamento(
            $where,
            $params,
            $filtros,
            'l'
        );


        /*
        |--------------------------------------------------------------------------
        | IMPORTANTE
        |--------------------------------------------------------------------------
        |
        | No plano da empresa 2, IVA_DEDUTIVEL e IVA_LIQUIDADO
        | estão nas contas sintéticas 1849/1850 e as contas analíticas
        | são suas filhas.
        |
        | Por isso verificamos a própria conta E a conta pai.
        |--------------------------------------------------------------------------
        */

        $sql = "
            SELECT

                l.id
                    AS lancamento_id,

                l.data_lancamento,

                l.numero_documento,

                l.referencia,

                l.origem,

                c.codigo
                    AS conta_codigo,

                c.nome
                    AS conta_nome,

                COALESCE(
                    c.integracao_codigo,
                    pai.integracao_codigo
                ) AS integracao_codigo,

                COALESCE(
                    i.descricao,
                    l.descricao
                ) AS descricao,

                i.debito,

                i.credito,

                CASE

                WHEN
                    COALESCE(
                        c.integracao_codigo,
                        pai.integracao_codigo,
                        ''
                    ) LIKE 'IVA_DEDUTIVEL%'
                    OR c.codigo = '4.4.3.2'
                    OR c.codigo LIKE '4.4.3.2.%'
                    OR LOWER(c.nome) LIKE '%iva dedut%'

                THEN 'IVA_DEDUTIVEL'

                WHEN
                    COALESCE(
                        c.integracao_codigo,
                        pai.integracao_codigo,
                        ''
                    ) LIKE 'IVA_LIQUIDADO%'
                    OR c.codigo = '4.4.3.3'
                    OR c.codigo LIKE '4.4.3.3.%'
                    OR LOWER(c.nome) LIKE '%iva liquid%'

                THEN 'IVA_LIQUIDADO'

                ELSE 'OUTRO'

            END AS tipo_iva

            FROM
                lancamento_contabilistico_itens i

            INNER JOIN
                lancamentos_contabilisticos l
                ON l.id = i.lancamento_id

            INNER JOIN
                contas_contabilisticas c
                ON c.id = i.conta_contabil_id
               AND c.empresa_id = l.empresa_id

            LEFT JOIN
                contas_contabilisticas pai
                ON pai.id = c.conta_pai_id
               AND pai.empresa_id = c.empresa_id

            WHERE "
            . implode(
                ' AND ',
                $where
            )
            . "

              AND
              (
                    COALESCE(
                        c.integracao_codigo,
                        pai.integracao_codigo,
                        ''
                    ) LIKE 'IVA_DEDUTIVEL%'

                    OR COALESCE(
                        c.integracao_codigo,
                        pai.integracao_codigo,
                        ''
                    ) LIKE 'IVA_LIQUIDADO%'

                    OR c.codigo = '4.4.3.2'
                    OR c.codigo LIKE '4.4.3.2.%'
                    OR c.codigo = '4.4.3.3'
                    OR c.codigo LIKE '4.4.3.3.%'
                    OR LOWER(c.nome) LIKE '%iva dedut%'
                    OR LOWER(c.nome) LIKE '%iva liquid%'
              )

            ORDER BY
                l.data_lancamento ASC,
                l.id ASC,
                i.ordem ASC
        ";


        $stmt =
            $this->db->prepare($sql);


        $stmt->execute($params);


        $registos =
            $stmt->fetchAll(
                PDO::FETCH_ASSOC
            );


        $ivaLiquidado = 0.0;
        $ivaDedutivel = 0.0;


        foreach ($registos as $registo) {

            if (
                $registo['tipo_iva']
                === 'IVA_LIQUIDADO'
            ) {

                /*
                | IVA liquidado é normalmente credor.
                */

                $ivaLiquidado +=
                    (float)$registo['credito']
                    -
                    (float)$registo['debito'];
            } elseif (
                $registo['tipo_iva']
                === 'IVA_DEDUTIVEL'
            ) {

                /*
                | IVA dedutível é normalmente devedor.
                */

                $ivaDedutivel +=
                    (float)$registo['debito']
                    -
                    (float)$registo['credito'];
            }
        }


        $ivaLiquidado = round($ivaLiquidado, 2);
        $ivaDedutivel = round($ivaDedutivel, 2);

        if (abs($ivaLiquidado) < 0.005) {
            $ivaLiquidado = 0.0;
        }

        if (abs($ivaDedutivel) < 0.005) {
            $ivaDedutivel = 0.0;
        }

        $apuramento = round(
            $ivaLiquidado - $ivaDedutivel,
            2
        );


        return [

            'resumo' => [

                'iva_liquidado' =>
                $ivaLiquidado,

                'iva_dedutivel' =>
                $ivaDedutivel,

                'iva_pagar' =>
                $apuramento > 0
                    ? $apuramento
                    : 0,

                'iva_recuperar' =>
                $apuramento < 0
                    ? abs($apuramento)
                    : 0
            ],

            'registos' =>
            $registos
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | LISTAR PERÍODOS
    |--------------------------------------------------------------------------
    */

    public function listarPeriodos(
        int $empresaId
    ): array {

        $stmt =
            $this->db->prepare("
                SELECT

                    p.id,

                    p.exercicio_id,

                    p.numero,

                    p.nome,

                    p.data_inicio,

                    p.data_fim,

                    s.nome
                        AS estado

                FROM periodos_contabilisticos p

                LEFT JOIN status s
                    ON s.id = p.status_id

                WHERE p.empresa_id =
                    :empresa_id

                ORDER BY
                    p.data_inicio DESC,
                    p.numero DESC
            ");


        $stmt->execute([
            ':empresa_id' =>
            $empresaId
        ]);


        return $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );
    }


    /*
    |--------------------------------------------------------------------------
    | LISTAR DIÁRIOS
    |--------------------------------------------------------------------------
    */

    public function listarDiarios(
        int $empresaId
    ): array {

        $stmt =
            $this->db->prepare("
                SELECT
                    id,
                    codigo,
                    nome,
                    tipo

                FROM diarios_contabilisticos

                WHERE empresa_id =
                    :empresa_id

                ORDER BY
                    codigo ASC,
                    nome ASC
            ");


        $stmt->execute([
            ':empresa_id' =>
            $empresaId
        ]);


        return $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );
    }


    /*
    |--------------------------------------------------------------------------
    | LISTAR CONTAS
    |--------------------------------------------------------------------------
    */

    public function listarContas(
        int $empresaId
    ): array {

        $stmt =
            $this->db->prepare("
                SELECT
                    id,
                    codigo,
                    nome,
                    natureza,
                    grupo

                FROM contas_contabilisticas

                WHERE empresa_id =
                    :empresa_id

                  AND tipo =
                    'ANALITICA'

                  AND aceita_lancamento = 1

                ORDER BY
                    codigo ASC
            ");


        $stmt->execute([
            ':empresa_id' =>
            $empresaId
        ]);


        return $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );
    }


    /*
    |--------------------------------------------------------------------------
    | LISTAR FILIAIS
    |--------------------------------------------------------------------------
    */

    public function listarFiliais(
        int $empresaId
    ): array {

        $stmt =
            $this->db->prepare("
                SELECT
                    id,
                    nome

                FROM filiais

                WHERE empresa_id =
                    :empresa_id

                ORDER BY nome ASC
            ");


        $stmt->execute([
            ':empresa_id' =>
            $empresaId
        ]);


        return $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );
    }


    /*
    |--------------------------------------------------------------------------
    | BUSCAR CONTA
    |--------------------------------------------------------------------------
    */

    private function buscarConta(
        int $empresaId,
        int $contaId
    ): ?array {

        $stmt =
            $this->db->prepare("
                SELECT
                    id,
                    codigo,
                    nome,
                    natureza,
                    grupo

                FROM contas_contabilisticas

                WHERE id = :id
                  AND empresa_id = :empresa_id

                LIMIT 1
            ");


        $stmt->execute([
            ':id' =>
            $contaId,

            ':empresa_id' =>
            $empresaId
        ]);


        $conta =
            $stmt->fetch(
                PDO::FETCH_ASSOC
            );


        return $conta ?: null;
    }


    /*
    |--------------------------------------------------------------------------
    | RESULTADO ATÉ À DATA
    |--------------------------------------------------------------------------
    */

    private function resultadoAteData(
        int $empresaId,
        string $dataReferencia,
        ?int $filialId
    ): float {

        $statusId =
            $this->statusContabilizadoId();


        $where = [
            'l.empresa_id = :empresa_id',
            'l.status_id = :status_id',

            "l.origem <> 'ENCERRAMENTO'",

            "c.grupo IN (
                'RENDIMENTO',
                'GASTO'
            )"
        ];


        $params = [
            ':empresa_id' =>
            $empresaId,

            ':status_id' =>
            $statusId
        ];


        if ($dataReferencia !== '') {

            $where[] =
                'l.data_lancamento <= :data_referencia';

            $params[':data_referencia'] =
                $dataReferencia;
        }


        if (
            $filialId !== null
            && $filialId > 0
        ) {

            $where[] =
                'l.filial_id = :filial_id';

            $params[':filial_id'] =
                $filialId;
        }


        $stmt =
            $this->db->prepare(
                "
                SELECT

                    COALESCE(
                        SUM(
                            CASE

                                WHEN
                                    c.grupo =
                                    'RENDIMENTO'

                                THEN
                                    i.credito
                                    - i.debito

                                ELSE 0

                            END
                        ),
                        0
                    ) AS rendimentos,

                    COALESCE(
                        SUM(
                            CASE

                                WHEN
                                    c.grupo =
                                    'GASTO'

                                THEN
                                    i.debito
                                    - i.credito

                                ELSE 0

                            END
                        ),
                        0
                    ) AS gastos

                FROM
                    lancamento_contabilistico_itens i

                INNER JOIN
                    lancamentos_contabilisticos l
                    ON l.id = i.lancamento_id

                INNER JOIN
                    contas_contabilisticas c
                    ON c.id =
                        i.conta_contabil_id

                WHERE "
                    . implode(
                        ' AND ',
                        $where
                    )
            );


        $stmt->execute($params);


        $dados =
            $stmt->fetch(
                PDO::FETCH_ASSOC
            ) ?: [];


        return
            (float)(
                $dados['rendimentos']
                ?? 0
            )
            -
            (float)(
                $dados['gastos']
                ?? 0
            );
    }


    /*
    |--------------------------------------------------------------------------
    | FILTROS COMUNS
    |--------------------------------------------------------------------------
    */

    private function aplicarFiltrosLancamento(
        array &$where,
        array &$params,
        array $filtros,
        string $alias = 'l',
        bool $aplicarConta = true
    ): void {

        if (
            !empty($filtros['data_inicio'])
        ) {

            $where[] =
                "{$alias}.data_lancamento >= :data_inicio";

            $params[':data_inicio'] =
                $filtros['data_inicio'];
        }


        if (
            !empty($filtros['data_fim'])
        ) {

            $where[] =
                "{$alias}.data_lancamento <= :data_fim";

            $params[':data_fim'] =
                $filtros['data_fim'];
        }


        if (
            !empty($filtros['periodo_id'])
        ) {

            $where[] =
                "{$alias}.periodo_id = :periodo_id";

            $params[':periodo_id'] =
                (int)$filtros['periodo_id'];
        }


        if (
            !empty($filtros['diario_id'])
        ) {

            $where[] =
                "{$alias}.diario_id = :diario_id";

            $params[':diario_id'] =
                (int)$filtros['diario_id'];
        }


        if (
            !empty($filtros['filial_id'])
        ) {

            $where[] =
                "{$alias}.filial_id = :filial_id";

            $params[':filial_id'] =
                (int)$filtros['filial_id'];
        }
    }
}
