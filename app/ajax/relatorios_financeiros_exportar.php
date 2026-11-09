<?php

declare(strict_types=1);

if (
    session_status()
    !== PHP_SESSION_ACTIVE
) {
    session_start();
}


/*
|--------------------------------------------------------------------------
| Dependências
|--------------------------------------------------------------------------
*/

require_once __DIR__
    . '/../core/Database.php';

require_once __DIR__
    . '/../models/RelatorioFinanceiro.php';

require_once __DIR__
    . '/../controllers/RelatorioFinanceiroController.php';


/*
|--------------------------------------------------------------------------
| Empresa
|--------------------------------------------------------------------------
*/

$empresaId =
    (int)(
        $_SESSION['empresa_id']
        ?? $_SESSION['usuario']['empresa_id']
        ?? $_SESSION['user']['empresa_id']
        ?? 0
    );


if ($empresaId <= 0) {

    http_response_code(401);

    exit(
        'Empresa não identificada na sessão.'
    );
}


/*
|--------------------------------------------------------------------------
| Parâmetros
|--------------------------------------------------------------------------
*/

$tipo =
    strtoupper(
        trim(
            (string)(
                $_GET['tipo']
                ?? 'FLUXO_CAIXA'
            )
        )
    );


$dados = [

    'tipo' =>
        $tipo,

    'data_inicio' =>
        trim(
            (string)(
                $_GET['data_inicio']
                ?? ''
            )
        ),

    'data_fim' =>
        trim(
            (string)(
                $_GET['data_fim']
                ?? ''
            )
        ),

    'filial_id' =>
        $_GET['filial_id']
        ?? '',

    'banco_id' =>
        $_GET['banco_id']
        ?? '',

    'centro_custo_id' =>
        $_GET['centro_custo_id']
        ?? '',

    'estado' =>
        trim(
            (string)(
                $_GET['estado']
                ?? ''
            )
        ),

    'tipo_movimento' =>
        trim(
            (string)(
                $_GET['tipo_movimento']
                ?? ''
            )
        ),

    'pesquisa' =>
        trim(
            (string)(
                $_GET['pesquisa']
                ?? ''
            )
        )
];


try {

    /*
    |--------------------------------------------------------------------------
    | Base de dados
    |--------------------------------------------------------------------------
    */

    $db =
        Database::conn();


    /*
    |--------------------------------------------------------------------------
    | Controller
    |--------------------------------------------------------------------------
    */

    $controller =
        new RelatorioFinanceiroController(
            $db
        );


    /*
    |--------------------------------------------------------------------------
    | Gerar relatório
    |--------------------------------------------------------------------------
    */

    $resultado =
        $controller->relatorio(
            $empresaId,
            $dados
        );


    if (
        empty(
            $resultado['success']
        )
    ) {

        throw new RuntimeException(
            $resultado['message']
            ?? 'Não foi possível gerar o relatório.'
        );
    }


    $relatorio =
        $resultado['data']
        ?? [];


    $resumo =
        $relatorio['resumo']
        ?? [];


    $registos =
        $relatorio['registos']
        ?? [];


    /*
    |--------------------------------------------------------------------------
    | Configurações
    |--------------------------------------------------------------------------
    */

    $configuracoes = [

        /*
        |--------------------------------------------------------------------------
        | FLUXO DE CAIXA
        |--------------------------------------------------------------------------
        */

        'FLUXO_CAIXA' => [

            'titulo' =>
                'Relatório de Fluxo de Caixa',

            'colunas' => [

                'data_movimento' =>
                    'Data',

                'origem_financeira' =>
                    'Fonte',

                'conta' =>
                    'Conta',

                'tipo' =>
                    'Tipo',

                'origem' =>
                    'Origem',

                'descricao' =>
                    'Descrição',

                'valor' =>
                    'Valor',

                'filial' =>
                    'Filial'
            ],

            'moeda' => [
                'valor'
            ],

            'datas_hora' => [
                'data_movimento'
            ],

            'datas' => [],

            'percentagem' => [],

            'resumo' => [

                'total_entradas' =>
                    'Entradas',

                'total_saidas' =>
                    'Saídas',

                'saldo_periodo' =>
                    'Saldo do período',

                'total_movimentos' =>
                    'Movimentos'
            ],

            'resumo_moeda' => [
                'total_entradas',
                'total_saidas',
                'saldo_periodo'
            ]
        ],


        /*
        |--------------------------------------------------------------------------
        | RECEITAS E DESPESAS
        |--------------------------------------------------------------------------
        */

        'RECEITAS_DESPESAS' => [

            'titulo' =>
                'Relatório de Receitas e Despesas',

            'colunas' => [

                'data_movimento' =>
                    'Data',

                'natureza' =>
                    'Natureza',

                'fonte' =>
                    'Fonte',

                'conta' =>
                    'Conta',

                'categoria' =>
                    'Categoria',

                'descricao' =>
                    'Descrição',

                'centro_custo' =>
                    'Centro de custo',

                'valor' =>
                    'Valor'
            ],

            'moeda' => [
                'valor'
            ],

            'datas_hora' => [
                'data_movimento'
            ],

            'datas' => [],

            'percentagem' => [],

            'resumo' => [

                'total_receitas' =>
                    'Receitas',

                'total_despesas' =>
                    'Despesas',

                'resultado' =>
                    'Resultado',

                'margem' =>
                    'Margem'
            ],

            'resumo_moeda' => [
                'total_receitas',
                'total_despesas',
                'resultado'
            ],

            'resumo_percentagem' => [
                'margem'
            ]
        ],


        /*
        |--------------------------------------------------------------------------
        | CONTAS A RECEBER
        |--------------------------------------------------------------------------
        */

        'CONTAS_RECEBER' => [

            'titulo' =>
                'Relatório de Contas a Receber',

            'colunas' => [

                'numero_documento' =>
                    'Documento',

                'cliente' =>
                    'Cliente',

                'data_emissao' =>
                    'Emissão',

                'data_vencimento' =>
                    'Vencimento',

                'valor' =>
                    'Valor',

                'valor_recebido' =>
                    'Recebido',

                'saldo' =>
                    'Saldo',

                'estado' =>
                    'Estado',

                'vencido' =>
                    'Situação'
            ],

            'moeda' => [
                'valor',
                'valor_recebido',
                'saldo'
            ],

            'datas_hora' => [],

            'datas' => [
                'data_emissao',
                'data_vencimento'
            ],

            'percentagem' => [],

            'resumo' => [

                'total_receber' =>
                    'Total a receber',

                'total_recebido' =>
                    'Recebido',

                'total_pendente' =>
                    'Pendente',

                'total_vencido' =>
                    'Vencido'
            ],

            'resumo_moeda' => [
                'total_receber',
                'total_recebido',
                'total_pendente',
                'total_vencido'
            ]
        ],


        /*
        |--------------------------------------------------------------------------
        | CONTAS A PAGAR
        |--------------------------------------------------------------------------
        */

        'CONTAS_PAGAR' => [

            'titulo' =>
                'Relatório de Contas a Pagar',

            'colunas' => [

                'numero_documento' =>
                    'Documento',

                'fornecedor' =>
                    'Fornecedor',

                'data_emissao' =>
                    'Emissão',

                'data_vencimento' =>
                    'Vencimento',

                'valor' =>
                    'Valor',

                'valor_pago' =>
                    'Pago',

                'saldo' =>
                    'Saldo',

                'estado' =>
                    'Estado',

                'vencido' =>
                    'Situação'
            ],

            'moeda' => [
                'valor',
                'valor_pago',
                'saldo'
            ],

            'datas_hora' => [],

            'datas' => [
                'data_emissao',
                'data_vencimento'
            ],

            'percentagem' => [],

            'resumo' => [

                'total_pagar' =>
                    'Total a pagar',

                'total_pago' =>
                    'Pago',

                'total_pendente' =>
                    'Pendente',

                'total_vencido' =>
                    'Vencido'
            ],

            'resumo_moeda' => [
                'total_pagar',
                'total_pago',
                'total_pendente',
                'total_vencido'
            ]
        ],


        /*
        |--------------------------------------------------------------------------
        | CENTROS DE CUSTO
        |--------------------------------------------------------------------------
        */

        'CENTROS_CUSTO' => [

            'titulo' =>
                'Relatório de Centros de Custo',

            'colunas' => [

                'codigo' =>
                    'Código',

                'nome' =>
                    'Centro de custo',

                'tipo' =>
                    'Tipo',

                'responsavel' =>
                    'Responsável',

                'orcamento_mensal' =>
                    'Orçamento',

                'receitas' =>
                    'Receitas',

                'despesas' =>
                    'Despesas',

                'resultado' =>
                    'Resultado',

                'percentual_orcamento' =>
                    'Orçamento usado'
            ],

            'moeda' => [
                'orcamento_mensal',
                'receitas',
                'despesas',
                'resultado'
            ],

            'datas_hora' => [],

            'datas' => [],

            'percentagem' => [
                'percentual_orcamento'
            ],

            'resumo' => [

                'total_centros' =>
                    'Centros de custo',

                'total_receitas' =>
                    'Receitas',

                'total_despesas' =>
                    'Despesas',

                'resultado' =>
                    'Resultado'
            ],

            'resumo_moeda' => [
                'total_receitas',
                'total_despesas',
                'resultado'
            ]
        ],


        /*
        |--------------------------------------------------------------------------
        | MOVIMENTAÇÃO BANCÁRIA
        |--------------------------------------------------------------------------
        */

        'MOVIMENTACAO_BANCARIA' => [

            'titulo' =>
                'Relatório de Movimentação Bancária',

            'colunas' => [

                'data_movimento' =>
                    'Data',

                'nome_banco' =>
                    'Banco',

                'conta' =>
                    'Conta',

                'tipo' =>
                    'Tipo',

                'categoria' =>
                    'Categoria',

                'descricao' =>
                    'Descrição',

                'valor' =>
                    'Valor',

                'saldo_anterior' =>
                    'Saldo anterior',

                'saldo_posterior' =>
                    'Saldo posterior',

                'conciliado' =>
                    'Conciliação'
            ],

            'moeda' => [
                'valor',
                'saldo_anterior',
                'saldo_posterior'
            ],

            'datas_hora' => [
                'data_movimento'
            ],

            'datas' => [],

            'percentagem' => [],

            'resumo' => [

                'total_entradas' =>
                    'Entradas',

                'total_saidas' =>
                    'Saídas',

                'saldo_liquido' =>
                    'Saldo líquido',

                'total_movimentos' =>
                    'Movimentos'
            ],

            'resumo_moeda' => [
                'total_entradas',
                'total_saidas',
                'saldo_liquido'
            ]
        ]
    ];


    if (
        !isset(
            $configuracoes[$tipo]
        )
    ) {

        throw new RuntimeException(
            'Tipo de relatório financeiro inválido.'
        );
    }


    $config =
        $configuracoes[$tipo];


    /*
    |--------------------------------------------------------------------------
    | Nomes auxiliares dos filtros
    |--------------------------------------------------------------------------
    */

    $nomeFilial =
        'Todas as filiais';


    if (
        !empty(
            $dados['filial_id']
        )
    ) {

        $stmt =
            $db->prepare("
                SELECT nome

                FROM filiais

                WHERE id = :id
                  AND empresa_id = :empresa_id

                LIMIT 1
            ");


        $stmt->execute([
            ':id' =>
                (int)$dados['filial_id'],

            ':empresa_id' =>
                $empresaId
        ]);


        $valor =
            $stmt->fetchColumn();


        if ($valor !== false) {
            $nomeFilial =
                (string)$valor;
        }
    }


    $nomeBanco =
        'Todas as contas';


    if (
        !empty(
            $dados['banco_id']
        )
    ) {

        $stmt =
            $db->prepare("
                SELECT
                    CONCAT(
                        nome_banco,
                        ' - ',
                        conta
                    )

                FROM bancos

                WHERE id = :id
                  AND empresa_id = :empresa_id

                LIMIT 1
            ");


        $stmt->execute([
            ':id' =>
                (int)$dados['banco_id'],

            ':empresa_id' =>
                $empresaId
        ]);


        $valor =
            $stmt->fetchColumn();


        if ($valor !== false) {
            $nomeBanco =
                (string)$valor;
        }
    }


    $nomeCentro =
        'Todos os centros';


    if (
        !empty(
            $dados['centro_custo_id']
        )
    ) {

        $stmt =
            $db->prepare("
                SELECT
                    CONCAT(
                        codigo,
                        ' - ',
                        nome
                    )

                FROM centros_custo

                WHERE id = :id
                  AND empresa_id = :empresa_id

                LIMIT 1
            ");


        $stmt->execute([
            ':id' =>
                (int)$dados['centro_custo_id'],

            ':empresa_id' =>
                $empresaId
        ]);


        $valor =
            $stmt->fetchColumn();


        if ($valor !== false) {
            $nomeCentro =
                (string)$valor;
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Nome do ficheiro
    |--------------------------------------------------------------------------
    */

    $nomeFicheiro =
        'relatorio_financeiro_'
        . strtolower($tipo)
        . '_'
        . date('Ymd_His')
        . '.xls';


    /*
    |--------------------------------------------------------------------------
    | Headers
    |--------------------------------------------------------------------------
    */

    header(
        'Content-Type: application/vnd.ms-excel; charset=UTF-8'
    );


    header(
        'Content-Disposition: attachment; filename="'
        . $nomeFicheiro
        . '"'
    );


    header(
        'Cache-Control: max-age=0'
    );


    header(
        'Pragma: public'
    );


    /*
    |--------------------------------------------------------------------------
    | BOM UTF-8
    |--------------------------------------------------------------------------
    */

    echo "\xEF\xBB\xBF";


    /*
    |--------------------------------------------------------------------------
    | Início documento
    |--------------------------------------------------------------------------
    */

    echo '
        <html>

        <head>

            <meta charset="UTF-8">

            <style>

                body {
                    font-family:
                        Arial,
                        sans-serif;
                }

                h2 {
                    margin-bottom: 5px;
                }

                table {
                    border-collapse:
                        collapse;
                }

                th,
                td {
                    border:
                        1px solid #999;

                    padding:
                        6px;
                }

                th {
                    background:
                        #eeeeee;

                    font-weight:
                        bold;
                }

                .numero {
                    text-align:
                        right;
                }

            </style>

        </head>

        <body>
    ';


    /*
    |--------------------------------------------------------------------------
    | Título
    |--------------------------------------------------------------------------
    */

    echo '<h2>';

    echo htmlspecialchars(
        $config['titulo'],
        ENT_QUOTES,
        'UTF-8'
    );

    echo '</h2>';


    echo '<div>';

    echo 'Gerado em: '
        . date(
            'd/m/Y H:i:s'
        );

    echo '</div>';


    /*
    |--------------------------------------------------------------------------
    | Filtros
    |--------------------------------------------------------------------------
    */

    echo '<br>';

    echo '<table>';

    echo '<tr>';

    echo '<th>Data inicial</th>';
    echo '<th>Data final</th>';
    echo '<th>Filial</th>';


    if (
        $tipo
        === 'MOVIMENTACAO_BANCARIA'
    ) {
        echo '<th>Conta bancária</th>';
    }


    if (
        in_array(
            $tipo,
            [
                'RECEITAS_DESPESAS',
                'CENTROS_CUSTO',
                'MOVIMENTACAO_BANCARIA'
            ],
            true
        )
    ) {
        echo '<th>Centro de custo</th>';
    }


    if (
        in_array(
            $tipo,
            [
                'CONTAS_RECEBER',
                'CONTAS_PAGAR'
            ],
            true
        )
    ) {
        echo '<th>Estado</th>';
    }


    if (
        $tipo
        === 'MOVIMENTACAO_BANCARIA'
    ) {
        echo '<th>Tipo de movimento</th>';
    }


    echo '<th>Pesquisa</th>';

    echo '</tr>';


    echo '<tr>';


    echo '<td>'
        . htmlspecialchars(
            $dados['data_inicio']
                ?: 'Todas',
            ENT_QUOTES,
            'UTF-8'
        )
        . '</td>';


    echo '<td>'
        . htmlspecialchars(
            $dados['data_fim']
                ?: 'Todas',
            ENT_QUOTES,
            'UTF-8'
        )
        . '</td>';


    echo '<td>'
        . htmlspecialchars(
            $nomeFilial,
            ENT_QUOTES,
            'UTF-8'
        )
        . '</td>';


    if (
        $tipo
        === 'MOVIMENTACAO_BANCARIA'
    ) {

        echo '<td>'
            . htmlspecialchars(
                $nomeBanco,
                ENT_QUOTES,
                'UTF-8'
            )
            . '</td>';
    }


    if (
        in_array(
            $tipo,
            [
                'RECEITAS_DESPESAS',
                'CENTROS_CUSTO',
                'MOVIMENTACAO_BANCARIA'
            ],
            true
        )
    ) {

        echo '<td>'
            . htmlspecialchars(
                $nomeCentro,
                ENT_QUOTES,
                'UTF-8'
            )
            . '</td>';
    }


    if (
        in_array(
            $tipo,
            [
                'CONTAS_RECEBER',
                'CONTAS_PAGAR'
            ],
            true
        )
    ) {

        echo '<td>'
            . htmlspecialchars(
                $dados['estado']
                    ?: 'Todos',
                ENT_QUOTES,
                'UTF-8'
            )
            . '</td>';
    }


    if (
        $tipo
        === 'MOVIMENTACAO_BANCARIA'
    ) {

        echo '<td>'
            . htmlspecialchars(
                $dados['tipo_movimento']
                    ?: 'Todos',
                ENT_QUOTES,
                'UTF-8'
            )
            . '</td>';
    }


    echo '<td>'
        . htmlspecialchars(
            $dados['pesquisa']
                ?: '-',
            ENT_QUOTES,
            'UTF-8'
        )
        . '</td>';


    echo '</tr>';

    echo '</table>';


    /*
    |--------------------------------------------------------------------------
    | Resumo
    |--------------------------------------------------------------------------
    */

    echo '<br>';

    echo '<table>';

    echo '<tr>';


    foreach (
        $config['resumo']
        as $campo => $tituloResumo
    ) {

        echo '<th>';

        echo htmlspecialchars(
            $tituloResumo,
            ENT_QUOTES,
            'UTF-8'
        );

        echo '</th>';
    }


    echo '</tr>';

    echo '<tr>';


    foreach (
        $config['resumo']
        as $campo => $tituloResumo
    ) {

        $valor =
            $resumo[$campo]
            ?? 0;


        if (
            in_array(
                $campo,
                $config['resumo_moeda']
                    ?? [],
                true
            )
        ) {

            $valor =
                number_format(
                    (float)$valor,
                    2,
                    ',',
                    '.'
                )
                . ' MTn';

        } elseif (
            in_array(
                $campo,
                $config['resumo_percentagem']
                    ?? [],
                true
            )
        ) {

            $valor =
                number_format(
                    (float)$valor,
                    2,
                    ',',
                    '.'
                )
                . '%';
        }


        echo '<td>';

        echo htmlspecialchars(
            (string)$valor,
            ENT_QUOTES,
            'UTF-8'
        );

        echo '</td>';
    }


    echo '</tr>';

    echo '</table>';


    /*
    |--------------------------------------------------------------------------
    | Tabela principal
    |--------------------------------------------------------------------------
    */

    echo '<br>';

    echo '<table>';

    echo '<thead>';

    echo '<tr>';


    foreach (
        $config['colunas']
        as $campo => $tituloColuna
    ) {

        echo '<th>';

        echo htmlspecialchars(
            $tituloColuna,
            ENT_QUOTES,
            'UTF-8'
        );

        echo '</th>';
    }


    echo '</tr>';

    echo '</thead>';

    echo '<tbody>';


    if (
        empty(
            $registos
        )
    ) {

        echo '<tr>';

        echo '<td colspan="'
            . count(
                $config['colunas']
            )
            . '">';

        echo 'Nenhum registo encontrado.';

        echo '</td>';

        echo '</tr>';

    } else {

        foreach (
            $registos as $registo
        ) {

            echo '<tr>';


            foreach (
                $config['colunas']
                as $campo => $tituloColuna
            ) {

                $valor =
                    $registo[$campo]
                    ?? '';


                /*
                |--------------------------------------------------------------------------
                | Situação vencida
                |--------------------------------------------------------------------------
                */

                if (
                    $campo === 'vencido'
                ) {

                    $valor =
                        (int)$valor === 1
                            ? 'Vencido'
                            : 'Regular';
                }


                /*
                |--------------------------------------------------------------------------
                | Conciliação
                |--------------------------------------------------------------------------
                */

                elseif (
                    $campo === 'conciliado'
                ) {

                    $valor =
                        (int)$valor === 1
                            ? 'Conciliado'
                            : 'Não conciliado';
                }


                /*
                |--------------------------------------------------------------------------
                | Moeda
                |--------------------------------------------------------------------------
                */

                elseif (
                    in_array(
                        $campo,
                        $config['moeda'],
                        true
                    )
                ) {

                    $valor =
                        number_format(
                            (float)$valor,
                            2,
                            ',',
                            '.'
                        )
                        . ' MTn';
                }


                /*
                |--------------------------------------------------------------------------
                | Percentagem
                |--------------------------------------------------------------------------
                */

                elseif (
                    in_array(
                        $campo,
                        $config['percentagem'],
                        true
                    )
                ) {

                    $valor =
                        number_format(
                            (float)$valor,
                            2,
                            ',',
                            '.'
                        )
                        . '%';
                }


                /*
                |--------------------------------------------------------------------------
                | Data/Hora
                |--------------------------------------------------------------------------
                */

                elseif (
                    in_array(
                        $campo,
                        $config['datas_hora'],
                        true
                    )
                    && !empty($valor)
                ) {

                    $timestamp =
                        strtotime(
                            (string)$valor
                        );


                    if (
                        $timestamp !== false
                    ) {

                        $valor =
                            date(
                                'd/m/Y H:i:s',
                                $timestamp
                            );
                    }
                }


                /*
                |--------------------------------------------------------------------------
                | Data
                |--------------------------------------------------------------------------
                */

                elseif (
                    in_array(
                        $campo,
                        $config['datas'],
                        true
                    )
                    && !empty($valor)
                ) {

                    $timestamp =
                        strtotime(
                            (string)$valor
                        );


                    if (
                        $timestamp !== false
                    ) {

                        $valor =
                            date(
                                'd/m/Y',
                                $timestamp
                            );
                    }
                }


                /*
                |--------------------------------------------------------------------------
                | Valores vazios
                |--------------------------------------------------------------------------
                */

                if (
                    $valor === null
                    || $valor === ''
                ) {
                    $valor = '-';
                }


                echo '<td>';

                echo htmlspecialchars(
                    (string)$valor,
                    ENT_QUOTES,
                    'UTF-8'
                );

                echo '</td>';
            }


            echo '</tr>';
        }
    }


    echo '</tbody>';

    echo '</table>';


    /*
    |--------------------------------------------------------------------------
    | Fim documento
    |--------------------------------------------------------------------------
    */

    echo '</body>';
    echo '</html>';

    exit;


} catch (Throwable $e) {

    error_log(
        sprintf(
            '[relatorios_financeiros_exportar] %s em %s:%d',
            $e->getMessage(),
            $e->getFile(),
            $e->getLine()
        )
    );


    http_response_code(500);


    echo htmlspecialchars(
        'Não foi possível exportar o relatório: '
        . $e->getMessage(),
        ENT_QUOTES,
        'UTF-8'
    );

    exit;
}