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
    . '/../models/RelatorioOperacional.php';

require_once __DIR__
    . '/../controllers/RelatorioOperacionalController.php';


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

    exit('Empresa não identificada na sessão.');
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
                ?? 'VENDAS'
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

    'estado' =>
    trim(
        (string)(
            $_GET['estado']
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


/*
|--------------------------------------------------------------------------
| Carregar relatório
|--------------------------------------------------------------------------
*/

try {

    $db =
        Database::conn();


    $controller =
        new RelatorioOperacionalController(
            $db
        );


    $resultado =
        $controller->relatorio(
            $empresaId,
            $dados
        );


    if (
        empty($resultado['success'])
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
    | Configuração por relatório
    |--------------------------------------------------------------------------
    */

    $configuracoes = [

        'VENDAS' => [

            'titulo' =>
            'Relatório de Vendas',

            'colunas' => [

                'data' =>
                'Data',

                'documento' =>
                'Documento',

                'cliente' =>
                'Cliente',

                'filial' =>
                'Filial',

                'subtotal' =>
                'Subtotal',

                'desconto' =>
                'Desconto',

                'imposto' =>
                'Imposto',

                'total' =>
                'Total',

                'estado' =>
                'Estado'
            ],

            'moeda' => [
                'subtotal',
                'desconto',
                'imposto',
                'total'
            ],

            'datas' => [
                'data'
            ],

            'resumo' => [

                'total_vendas' =>
                'Total de vendas',

                'valor_total' =>
                'Valor vendido',

                'total_descontos' =>
                'Descontos',

                'ticket_medio' =>
                'Ticket médio'
            ]
        ],


        'COMPRAS' => [

            'titulo' =>
            'Relatório de Compras',

            'colunas' => [

                'data' =>
                'Data',

                'documento' =>
                'Documento',

                'fornecedor' =>
                'Fornecedor',

                'filial' =>
                'Filial',

                'subtotal' =>
                'Subtotal',

                'imposto' =>
                'Imposto',

                'total' =>
                'Total',

                'estado' =>
                'Estado'
            ],

            'moeda' => [
                'subtotal',
                'imposto',
                'total'
            ],

            'datas' => [
                'data'
            ],

            'resumo' => [

                'total_compras' =>
                'Total de compras',

                'valor_total' =>
                'Valor comprado',

                'total_fornecedores' =>
                'Fornecedores',

                'valor_pendente' =>
                'Valor pendente'
            ]
        ],


        'STOCK' => [

            'titulo' =>
            'Relatório de Stock',

            'colunas' => [

                'codigo' =>
                'Código',

                'produto' =>
                'Produto',

                'armazem' =>
                'Armazém',

                'quantidade' =>
                'Quantidade',

                'stock_minimo' =>
                'Mínimo',

                'custo_medio' =>
                'Custo médio',

                'valor_stock' =>
                'Valor stock',

                'situacao' =>
                'Situação'
            ],

            'moeda' => [
                'custo_medio',
                'valor_stock'
            ],

            'datas' => [],

            'resumo' => [

                'total_produtos' =>
                'Produtos',

                'quantidade_stock' =>
                'Quantidade em stock',

                'valor_stock' =>
                'Valor do stock',

                'abaixo_minimo' =>
                'Abaixo do mínimo'
            ]
        ],


        'CLIENTES' => [

            'titulo' =>
            'Relatório de Clientes',

            'colunas' => [

                'cliente' =>
                'Cliente',

                'nuit' =>
                'NUIT',

                'contacto' =>
                'Contacto',

                'total_compras' =>
                'Compras',

                'valor_comprado' =>
                'Valor comprado',

                'saldo' =>
                'Saldo',

                'ultima_compra' =>
                'Última compra'
            ],

            'moeda' => [
                'valor_comprado',
                'saldo'
            ],

            'datas' => [
                'ultima_compra'
            ],

            'resumo' => [

                'total_clientes' =>
                'Clientes',

                'clientes_activos' =>
                'Clientes activos',

                'valor_comprado' =>
                'Valor comprado',

                'saldo_receber' =>
                'Saldo a receber'
            ]
        ],


        'FORNECEDORES' => [

            'titulo' =>
            'Relatório de Fornecedores',

            'colunas' => [

                'fornecedor' =>
                'Fornecedor',

                'nuit' =>
                'NUIT',

                'contacto' =>
                'Contacto',

                'total_compras' =>
                'Compras',

                'valor_comprado' =>
                'Valor comprado',

                'saldo' =>
                'Saldo',

                'ultima_compra' =>
                'Última compra'
            ],

            'moeda' => [
                'valor_comprado',
                'saldo'
            ],

            'datas' => [
                'ultima_compra'
            ],

            'resumo' => [

                'total_fornecedores' =>
                'Fornecedores',

                'fornecedores_activos' =>
                'Fornecedores activos',

                'valor_comprado' =>
                'Valor comprado',

                'saldo_pagar' =>
                'Saldo a pagar'
            ]
        ],


        'PRODUTOS' => [

            'titulo' =>
            'Relatório de Produtos',

            'colunas' => [

                'codigo' =>
                'Código',

                'produto' =>
                'Produto',

                'categoria' =>
                'Categoria',

                'quantidade_vendida' =>
                'Qtd. vendida',

                'valor_vendido' =>
                'Valor vendido',

                'stock_actual' =>
                'Stock actual',

                'estado' =>
                'Estado'
            ],

            'moeda' => [
                'valor_vendido'
            ],

            'datas' => [],

            'resumo' => [

                'total_produtos' =>
                'Produtos',

                'produtos_activos' =>
                'Produtos activos',

                'quantidade_vendida' =>
                'Quantidade vendida',

                'valor_vendido' =>
                'Valor vendido'
            ]
        ]
    ];


    if (
        !isset(
            $configuracoes[$tipo]
        )
    ) {

        throw new RuntimeException(
            'Tipo de relatório inválido.'
        );
    }


    $config =
        $configuracoes[$tipo];


    /*
    |--------------------------------------------------------------------------
    | Nome do ficheiro
    |--------------------------------------------------------------------------
    */

    $nomeFicheiro =
        'relatorio_'
        . strtolower($tipo)
        . '_'
        . date('Ymd_His')
        . '.xls';


    /*
    |--------------------------------------------------------------------------
    | Headers Excel
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
    | Documento HTML compatível com Excel
    |--------------------------------------------------------------------------
    */

    echo '
        <html>
        <head>
            <meta charset="UTF-8">

            <style>

                body {
                    font-family: Arial, sans-serif;
                }

                h2 {
                    margin-bottom: 5px;
                }

                table {
                    border-collapse: collapse;
                }

                th,
                td {
                    border: 1px solid #999;
                    padding: 6px;
                }

                th {
                    background: #eeeeee;
                    font-weight: bold;
                }

                .titulo {
                    font-size: 18px;
                    font-weight: bold;
                }

                .subtitulo {
                    font-size: 11px;
                }

                .valor {
                    text-align: right;
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


    echo '<div class="subtitulo">';

    echo 'Gerado em: '
        . date(
            'd/m/Y H:i:s'
        );

    echo '</div>';

    $labelEstado =
        $tipo === 'STOCK'
        ? 'Situação'
        : 'Estado';

    $nomeFilial =
        'Todas as filiais';

    $filialId =
        (int)(
            $dados['filial_id']
            ?? 0
        );

    if ($filialId > 0) {

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
            $filialId,

            ':empresa_id' =>
            $empresaId
        ]);

        $nomeEncontrado =
            $stmt->fetchColumn();

        if ($nomeEncontrado !== false) {
            $nomeFilial =
                (string)$nomeEncontrado;
        }
    }


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
    echo '<th>';

    echo htmlspecialchars(
        $labelEstado,
        ENT_QUOTES,
        'UTF-8'
    );

    echo '</th>';
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

    echo '<td>'
        . htmlspecialchars(
            $dados['estado']
                ?: 'Todos',
            ENT_QUOTES,
            'UTF-8'
        )
        . '</td>';

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
        as $campo => $titulo
    ) {

        echo '<th>';

        echo htmlspecialchars(
            $titulo,
            ENT_QUOTES,
            'UTF-8'
        );

        echo '</th>';
    }


    echo '</tr>';

    echo '<tr>';


    foreach (
        $config['resumo']
        as $campo => $titulo
    ) {

        $valor =
            $resumo[$campo]
            ?? 0;


        if (
            in_array(
                $campo,
                [
                    'valor_total',
                    'total_descontos',
                    'ticket_medio',
                    'valor_pendente',
                    'valor_stock',
                    'valor_comprado',
                    'saldo_receber',
                    'saldo_pagar',
                    'valor_vendido'
                ],
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
    | Registos
    |--------------------------------------------------------------------------
    */

    echo '<br>';

    echo '<table>';

    echo '<thead>';

    echo '<tr>';


    foreach (
        $config['colunas']
        as $campo => $titulo
    ) {

        echo '<th>';

        echo htmlspecialchars(
            $titulo,
            ENT_QUOTES,
            'UTF-8'
        );

        echo '</th>';
    }


    echo '</tr>';

    echo '</thead>';

    echo '<tbody>';


    if (!$registos) {

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

        foreach ($registos as $registo) {

            echo '<tr>';


            foreach (
                $config['colunas']
                as $campo => $titulo
            ) {

                $valor =
                    $registo[$campo]
                    ?? '';


                /*
                |--------------------------------------------------------------------------
                | Moeda
                |--------------------------------------------------------------------------
                */

                if (
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
                | Data
                |--------------------------------------------------------------------------
                */ elseif (
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


                    if ($timestamp !== false) {

                        $valor =
                            date(
                                'd/m/Y',
                                $timestamp
                            );
                    }
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


    echo '</body>';
    echo '</html>';


    exit;
} catch (Throwable $e) {

    error_log(
        sprintf(
            '[relatorios_operacionais_exportar] %s em %s:%d',
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
