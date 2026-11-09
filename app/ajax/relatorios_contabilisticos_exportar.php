<?php

declare(strict_types=1);


/*
|--------------------------------------------------------------------------
| SESSÃO
|--------------------------------------------------------------------------
*/

if (
    session_status()
    !== PHP_SESSION_ACTIVE
) {
    session_start();
}


/*
|--------------------------------------------------------------------------
| ERROS
|--------------------------------------------------------------------------
*/

error_reporting(E_ALL);

ini_set(
    'display_errors',
    '0'
);

ini_set(
    'log_errors',
    '1'
);


/*
|--------------------------------------------------------------------------
| DEPENDÊNCIAS
|--------------------------------------------------------------------------
*/

require_once __DIR__
    . '/../core/Database.php';

require_once __DIR__
    . '/../models/RelatorioContabilistico.php';

require_once __DIR__
    . '/../controllers/RelatorioContabilisticoController.php';


/*
|--------------------------------------------------------------------------
| HELPERS
|--------------------------------------------------------------------------
*/

function escaparExcelContabilistico(
    mixed $valor
): string {

    return htmlspecialchars(
        (string)$valor,
        ENT_QUOTES,
        'UTF-8'
    );
}


function moedaExcelContabilistico(
    mixed $valor
): string {

    return number_format(
        (float)$valor,
        2,
        ',',
        ' '
    )
        . ' MTn';
}


function percentagemExcelContabilistico(
    mixed $valor
): string {

    return number_format(
        (float)$valor,
        2,
        ',',
        ' '
    )
        . '%';
}


function dataExcelContabilistico(
    mixed $valor
): string {

    $valor =
        trim(
            (string)$valor
        );


    if ($valor === '') {
        return '-';
    }


    $timestamp =
        strtotime(
            $valor
        );


    if ($timestamp === false) {
        return $valor;
    }


    return date(
        'd/m/Y',
        $timestamp
    );
}


function saldoExcelContabilistico(
    mixed $devedor,
    mixed $credor
): string {

    $devedor =
        (float)$devedor;

    $credor =
        (float)$credor;


    if ($devedor > 0) {

        return moedaExcelContabilistico(
            $devedor
        )
            . ' D';
    }


    if ($credor > 0) {

        return moedaExcelContabilistico(
            $credor
        )
            . ' C';
    }


    return moedaExcelContabilistico(
        0
    );
}


/*
|--------------------------------------------------------------------------
| EMPRESA
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

    http_response_code(
        401
    );

    exit('Empresa não identificada na sessão.');
}


/*
|--------------------------------------------------------------------------
| TIPO
|--------------------------------------------------------------------------
*/

$tipo =
    strtoupper(
        trim(
            (string)(
                $_GET['tipo']
                ?? ''
            )
        )
    );


$tiposPermitidos = [

    'DIARIO',

    'RAZAO',

    'BALANCETE',

    'DRE',

    'BALANCO_PATRIMONIAL',

    'IVA'
];


if (
    !in_array(
        $tipo,
        $tiposPermitidos,
        true
    )
) {

    http_response_code(
        400
    );

    exit('Tipo de relatório inválido.');
}


/*
|--------------------------------------------------------------------------
| FILTROS
|--------------------------------------------------------------------------
*/

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

    'periodo_id' =>
    $_GET['periodo_id']
        ?? '',

    'diario_id' =>
    $_GET['diario_id']
        ?? '',

    'conta_id' =>
    $_GET['conta_id']
        ?? '',

    'filial_id' =>
    $_GET['filial_id']
        ?? '',

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
| EXECUTAR
|--------------------------------------------------------------------------
*/

try {

    $db =
        Database::conn();


    $controller =
        new RelatorioContabilisticoController(
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
            (string)(
                $resultado['message']
                ?? 'Não foi possível gerar o relatório.'
            )
        );
    }


    $dadosRelatorio =
        $resultado['data']
        ?? [];
} catch (Throwable $e) {

    error_log(
        sprintf(
            '[relatorios_contabilisticos_exportar] %s em %s:%d',
            $e->getMessage(),
            $e->getFile(),
            $e->getLine()
        )
    );


    http_response_code(
        500
    );


    exit($e->getMessage());
}


/*
|--------------------------------------------------------------------------
| TÍTULO
|--------------------------------------------------------------------------
*/

$titulos = [

    'DIARIO' =>
    'Relatório Diário',

    'RAZAO' =>
    'Razão Contabilística',

    'BALANCETE' =>
    'Balancete',

    'DRE' =>
    'Demonstração de Resultados',

    'BALANCO_PATRIMONIAL' =>
    'Balanço Patrimonial',

    'IVA' =>
    'Relatório de IVA'
];


$titulo =
    $titulos[$tipo]
    ?? 'Relatório Contabilístico';


/*
|--------------------------------------------------------------------------
| FICHEIRO
|--------------------------------------------------------------------------
*/

$nomeArquivo =
    strtolower(
        preg_replace(
            '/[^a-zA-Z0-9]+/',
            '_',
            $tipo
        )
    );


$nomeArquivo .=
    '_'
    . date('Ymd_His')
    . '.xls';


header(
    'Content-Type: application/vnd.ms-excel; charset=UTF-8'
);


header(
    'Content-Disposition: attachment; filename="'
        . $nomeArquivo
        . '"'
);


header(
    'Cache-Control: max-age=0'
);


/*
|--------------------------------------------------------------------------
| BOM UTF-8
|--------------------------------------------------------------------------
*/

echo "\xEF\xBB\xBF";

?>
<!DOCTYPE html>

<html lang="pt">

<head>

    <meta charset="UTF-8">

    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 11px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }

        th,
        td {
            border: 1px solid #999;
            padding: 5px;
        }

        th {
            background-color: #eeeeee;
            font-weight: bold;
        }

        .titulo {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .subtitulo {
            font-size: 11px;
            margin-bottom: 15px;
        }

        .numero {
            text-align: right;
        }

        .grupo {
            background-color: #dddddd;
            font-weight: bold;
        }

        .total {
            font-weight: bold;
            background-color: #f5f5f5;
        }

        .alerta {
            background-color: #fff3cd;
        }

        .normal {
            background-color: #d4edda;
        }
    </style>

</head>

<body>


    <div class="titulo">
        <?= escaparExcelContabilistico($titulo) ?>
    </div>


    <div class="subtitulo">

        Gerado em:

        <?= date('d/m/Y H:i:s') ?>

        <?php if ($dados['data_inicio'] !== ''): ?>

            |
            Data inicial:

            <?= escaparExcelContabilistico(
                dataExcelContabilistico(
                    $dados['data_inicio']
                )
            ) ?>

        <?php endif; ?>


        <?php if ($dados['data_fim'] !== ''): ?>

            |
            <?= $tipo === 'BALANCO_PATRIMONIAL'
                ? 'Data de referência'
                : 'Data final'
            ?>:

            <?= escaparExcelContabilistico(
                dataExcelContabilistico(
                    $dados['data_fim']
                )
            ) ?>

        <?php endif; ?>

    </div>


    <?php

    /*
|--------------------------------------------------------------------------
| DIÁRIO
|--------------------------------------------------------------------------
*/

    if ($tipo === 'DIARIO'):

        $resumo =
            $dadosRelatorio['resumo']
            ?? [];

        $registos =
            $dadosRelatorio['registos']
            ?? [];

    ?>

        <table>

            <tr>

                <th>Lançamentos</th>
                <th>Total Débito</th>
                <th>Total Crédito</th>
                <th>Diferença</th>

            </tr>

            <tr>

                <td>
                    <?= (int)(
                        $resumo['total_lancamentos']
                        ?? 0
                    ) ?>
                </td>

                <td class="numero">
                    <?= escaparExcelContabilistico(
                        moedaExcelContabilistico(
                            $resumo['total_debito']
                                ?? 0
                        )
                    ) ?>
                </td>

                <td class="numero">
                    <?= escaparExcelContabilistico(
                        moedaExcelContabilistico(
                            $resumo['total_credito']
                                ?? 0
                        )
                    ) ?>
                </td>

                <td class="numero">
                    <?= escaparExcelContabilistico(
                        moedaExcelContabilistico(
                            $resumo['diferenca']
                                ?? 0
                        )
                    ) ?>
                </td>

            </tr>

        </table>


        <table>

            <thead>

                <tr>

                    <th>Data</th>
                    <th>Diário</th>
                    <th>N.º</th>
                    <th>Documento</th>
                    <th>Conta</th>
                    <th>Descrição da Conta</th>
                    <th>Descrição</th>
                    <th>Entidade</th>
                    <th>Centro de Custo</th>
                    <th>Débito</th>
                    <th>Crédito</th>

                </tr>

            </thead>

            <tbody>

                <?php foreach ($registos as $linha): ?>

                    <tr>

                        <td>
                            <?= escaparExcelContabilistico(
                                dataExcelContabilistico(
                                    $linha['data_lancamento']
                                        ?? ''
                                )
                            ) ?>
                        </td>

                        <td>
                            <?= escaparExcelContabilistico(
                                $linha['diario_codigo']
                                    ?? '-'
                            ) ?>
                        </td>

                        <td>
                            <?= escaparExcelContabilistico(
                                $linha['numero']
                                    ?? '-'
                            ) ?>
                        </td>

                        <td>
                            <?= escaparExcelContabilistico(
                                $linha['numero_documento']
                                    ?? $linha['referencia']
                                    ?? '-'
                            ) ?>
                        </td>

                        <td>
                            <?= escaparExcelContabilistico(
                                $linha['conta_codigo']
                                    ?? '-'
                            ) ?>
                        </td>

                        <td>
                            <?= escaparExcelContabilistico(
                                $linha['conta_nome']
                                    ?? '-'
                            ) ?>
                        </td>

                        <td>
                            <?= escaparExcelContabilistico(
                                $linha['item_descricao']
                                    ?? '-'
                            ) ?>
                        </td>

                        <td>
                            <?= escaparExcelContabilistico(
                                $linha['entidade']
                                    ?? '-'
                            ) ?>
                        </td>

                        <td>
                            <?= escaparExcelContabilistico(
                                $linha['centro_custo']
                                    ?? '-'
                            ) ?>
                        </td>

                        <td class="numero">
                            <?= escaparExcelContabilistico(
                                moedaExcelContabilistico(
                                    $linha['debito']
                                        ?? 0
                                )
                            ) ?>
                        </td>

                        <td class="numero">
                            <?= escaparExcelContabilistico(
                                moedaExcelContabilistico(
                                    $linha['credito']
                                        ?? 0
                                )
                            ) ?>
                        </td>

                    </tr>

                <?php endforeach; ?>

            </tbody>

        </table>


    <?php

    /*
|--------------------------------------------------------------------------
| RAZÃO
|--------------------------------------------------------------------------
*/

    elseif ($tipo === 'RAZAO'):

        $conta =
            $dadosRelatorio['conta']
            ?? [];

        $resumo =
            $dadosRelatorio['resumo']
            ?? [];

        $registos =
            $dadosRelatorio['registos']
            ?? [];

    ?>

        <table>

            <tr>
                <th>Código</th>
                <th>Conta</th>
                <th>Natureza</th>
                <th>Grupo</th>
            </tr>

            <tr>

                <td>
                    <?= escaparExcelContabilistico(
                        $conta['codigo']
                            ?? '-'
                    ) ?>
                </td>

                <td>
                    <?= escaparExcelContabilistico(
                        $conta['nome']
                            ?? '-'
                    ) ?>
                </td>

                <td>
                    <?= escaparExcelContabilistico(
                        $conta['natureza']
                            ?? '-'
                    ) ?>
                </td>

                <td>
                    <?= escaparExcelContabilistico(
                        $conta['grupo']
                            ?? '-'
                    ) ?>
                </td>

            </tr>

        </table>


        <table>

            <tr>

                <th>Saldo Inicial</th>
                <th>Débitos</th>
                <th>Créditos</th>
                <th>Saldo Final</th>

            </tr>

            <tr>

                <td class="numero">

                    <?= escaparExcelContabilistico(
                        saldoExcelContabilistico(
                            $resumo['saldo_inicial_devedor'] ?? 0,

                            $resumo['saldo_inicial_credor'] ?? 0
                        )
                    ) ?>

                </td>

                <td class="numero">

                    <?= escaparExcelContabilistico(
                        moedaExcelContabilistico(
                            $resumo['total_debitos'] ?? 0
                        )
                    ) ?>

                </td>

                <td class="numero">

                    <?= escaparExcelContabilistico(
                        moedaExcelContabilistico(
                            $resumo['total_creditos'] ?? 0
                        )
                    ) ?>

                </td>

                <td class="numero">

                    <?= escaparExcelContabilistico(
                        saldoExcelContabilistico(
                            $resumo['saldo_final_devedor'] ?? 0,

                            $resumo['saldo_final_credor'] ?? 0
                        )
                    ) ?>

                </td>

            </tr>

        </table>


        <table>

            <thead>

                <tr>

                    <th>Data</th>
                    <th>Diário</th>
                    <th>Documento</th>
                    <th>Descrição</th>
                    <th>Origem</th>
                    <th>Entidade</th>
                    <th>Centro de Custo</th>
                    <th>Débito</th>
                    <th>Crédito</th>
                    <th>Saldo</th>

                </tr>

            </thead>

            <tbody>

                <?php foreach ($registos as $linha): ?>

                    <tr>

                        <td>
                            <?= escaparExcelContabilistico(
                                dataExcelContabilistico(
                                    $linha['data_lancamento']
                                        ?? ''
                                )
                            ) ?>
                        </td>

                        <td>
                            <?= escaparExcelContabilistico(
                                $linha['diario_codigo']
                                    ?? '-'
                            ) ?>
                        </td>

                        <td>
                            <?= escaparExcelContabilistico(
                                $linha['numero_documento']
                                    ?? $linha['referencia']
                                    ?? '-'
                            ) ?>
                        </td>

                        <td>
                            <?= escaparExcelContabilistico(
                                $linha['descricao']
                                    ?? '-'
                            ) ?>
                        </td>

                        <td>
                            <?= escaparExcelContabilistico(
                                $linha['origem']
                                    ?? '-'
                            ) ?>
                        </td>

                        <td>
                            <?= escaparExcelContabilistico(
                                $linha['entidade']
                                    ?? '-'
                            ) ?>
                        </td>

                        <td>
                            <?= escaparExcelContabilistico(
                                $linha['centro_custo']
                                    ?? '-'
                            ) ?>
                        </td>

                        <td class="numero">
                            <?= escaparExcelContabilistico(
                                moedaExcelContabilistico(
                                    $linha['debito']
                                        ?? 0
                                )
                            ) ?>
                        </td>

                        <td class="numero">
                            <?= escaparExcelContabilistico(
                                moedaExcelContabilistico(
                                    $linha['credito']
                                        ?? 0
                                )
                            ) ?>
                        </td>

                        <td class="numero">
                            <?= escaparExcelContabilistico(
                                saldoExcelContabilistico(
                                    $linha['saldo_devedor']
                                        ?? 0,

                                    $linha['saldo_credor']
                                        ?? 0
                                )
                            ) ?>
                        </td>

                    </tr>

                <?php endforeach; ?>

            </tbody>

        </table>


    <?php

    /*
|--------------------------------------------------------------------------
| BALANCETE
|--------------------------------------------------------------------------
*/

    elseif ($tipo === 'BALANCETE'):

        $resumo =
            $dadosRelatorio['resumo']
            ?? [];

        $registos =
            $dadosRelatorio['registos']
            ?? [];

    ?>

        <table>

            <tr>

                <th>Contas</th>
                <th>Débitos</th>
                <th>Créditos</th>
                <th>Diferença</th>

            </tr>

            <tr>

                <td>
                    <?= (int)(
                        $resumo['total_contas']
                        ?? 0
                    ) ?>
                </td>

                <td class="numero">
                    <?= escaparExcelContabilistico(
                        moedaExcelContabilistico(
                            $resumo['total_debito']
                                ?? 0
                        )
                    ) ?>
                </td>

                <td class="numero">
                    <?= escaparExcelContabilistico(
                        moedaExcelContabilistico(
                            $resumo['total_credito']
                                ?? 0
                        )
                    ) ?>
                </td>

                <td class="numero">
                    <?= escaparExcelContabilistico(
                        moedaExcelContabilistico(
                            $resumo['diferenca']
                                ?? 0
                        )
                    ) ?>
                </td>

            </tr>

        </table>


        <table>

            <thead>

                <tr>

                    <th>Código</th>
                    <th>Conta</th>
                    <th>Grupo</th>
                    <th>Natureza</th>
                    <th>Débito</th>
                    <th>Crédito</th>
                    <th>Saldo Devedor</th>
                    <th>Saldo Credor</th>

                </tr>

            </thead>

            <tbody>

                <?php foreach ($registos as $linha): ?>

                    <tr>

                        <td>
                            <?= escaparExcelContabilistico(
                                $linha['codigo']
                                    ?? '-'
                            ) ?>
                        </td>

                        <td>
                            <?= escaparExcelContabilistico(
                                $linha['nome']
                                    ?? '-'
                            ) ?>
                        </td>

                        <td>
                            <?= escaparExcelContabilistico(
                                $linha['grupo']
                                    ?? '-'
                            ) ?>
                        </td>

                        <td>
                            <?= escaparExcelContabilistico(
                                $linha['natureza']
                                    ?? '-'
                            ) ?>
                        </td>

                        <td class="numero">
                            <?= escaparExcelContabilistico(
                                moedaExcelContabilistico(
                                    $linha['debito']
                                        ?? 0
                                )
                            ) ?>
                        </td>

                        <td class="numero">
                            <?= escaparExcelContabilistico(
                                moedaExcelContabilistico(
                                    $linha['credito']
                                        ?? 0
                                )
                            ) ?>
                        </td>

                        <td class="numero">
                            <?= escaparExcelContabilistico(
                                moedaExcelContabilistico(
                                    $linha['saldo_devedor']
                                        ?? 0
                                )
                            ) ?>
                        </td>

                        <td class="numero">
                            <?= escaparExcelContabilistico(
                                moedaExcelContabilistico(
                                    $linha['saldo_credor']
                                        ?? 0
                                )
                            ) ?>
                        </td>

                    </tr>

                <?php endforeach; ?>

            </tbody>

        </table>


    <?php

    /*
|--------------------------------------------------------------------------
| DRE
|--------------------------------------------------------------------------
*/

    elseif ($tipo === 'DRE'):

        $resumo =
            $dadosRelatorio['resumo']
            ?? [];

        $rendimentos =
            $dadosRelatorio['rendimentos']
            ?? [];

        $gastos =
            $dadosRelatorio['gastos']
            ?? [];

    ?>

        <table>

            <tr>

                <th>Rendimentos</th>
                <th>Gastos</th>
                <th>Resultado Líquido</th>
                <th>Margem</th>

            </tr>

            <tr>

                <td class="numero">
                    <?= escaparExcelContabilistico(
                        moedaExcelContabilistico(
                            $resumo['total_rendimentos'] ?? 0
                        )
                    ) ?>
                </td>

                <td class="numero">
                    <?= escaparExcelContabilistico(
                        moedaExcelContabilistico(
                            $resumo['total_gastos'] ?? 0
                        )
                    ) ?>
                </td>

                <td class="numero">
                    <?= escaparExcelContabilistico(
                        moedaExcelContabilistico(
                            $resumo['resultado_liquido'] ?? 0
                        )
                    ) ?>
                </td>

                <td class="numero">
                    <?= escaparExcelContabilistico(
                        percentagemExcelContabilistico(
                            $resumo['margem']
                                ?? 0
                        )
                    ) ?>
                </td>

            </tr>

        </table>


        <table>

            <thead>

                <tr>

                    <th>Código</th>
                    <th>Conta</th>
                    <th>Valor</th>

                </tr>

            </thead>

            <tbody>

                <tr class="grupo">

                    <td colspan="3">
                        RENDIMENTOS
                    </td>

                </tr>


                <?php foreach ($rendimentos as $linha): ?>

                    <tr>

                        <td>
                            <?= escaparExcelContabilistico(
                                $linha['codigo']
                                    ?? '-'
                            ) ?>
                        </td>

                        <td>
                            <?= escaparExcelContabilistico(
                                $linha['nome']
                                    ?? '-'
                            ) ?>
                        </td>

                        <td class="numero">
                            <?= escaparExcelContabilistico(
                                moedaExcelContabilistico(
                                    $linha['valor']
                                        ?? 0
                                )
                            ) ?>
                        </td>

                    </tr>

                <?php endforeach; ?>


                <tr class="grupo">

                    <td colspan="3">
                        GASTOS
                    </td>

                </tr>


                <?php foreach ($gastos as $linha): ?>

                    <tr>

                        <td>
                            <?= escaparExcelContabilistico(
                                $linha['codigo']
                                    ?? '-'
                            ) ?>
                        </td>

                        <td>
                            <?= escaparExcelContabilistico(
                                $linha['nome']
                                    ?? '-'
                            ) ?>
                        </td>

                        <td class="numero">
                            <?= escaparExcelContabilistico(
                                moedaExcelContabilistico(
                                    $linha['valor']
                                        ?? 0
                                )
                            ) ?>
                        </td>

                    </tr>

                <?php endforeach; ?>


                <tr class="total">

                    <td colspan="2">
                        RESULTADO LÍQUIDO
                    </td>

                    <td class="numero">
                        <?= escaparExcelContabilistico(
                            moedaExcelContabilistico(
                                $resumo['resultado_liquido'] ?? 0
                            )
                        ) ?>
                    </td>

                </tr>

            </tbody>

        </table>


    <?php

    /*
|--------------------------------------------------------------------------
| BALANÇO PATRIMONIAL
|--------------------------------------------------------------------------
*/

    elseif (
        $tipo === 'BALANCO_PATRIMONIAL'
    ):

        $resumo =
            $dadosRelatorio['resumo']
            ?? [];

        $ativos =
            $dadosRelatorio['ativos']
            ?? [];

        $passivos =
            $dadosRelatorio['passivos']
            ?? [];

        $capital =
            $dadosRelatorio['capital_proprio'] ?? [];

    ?>

        <table>

            <tr>

                <th>Ativo</th>
                <th>Passivo</th>
                <th>Capital + Resultado</th>
                <th>Diferença</th>

            </tr>

            <tr>

                <td class="numero">
                    <?= escaparExcelContabilistico(
                        moedaExcelContabilistico(
                            $resumo['total_ativo'] ?? 0
                        )
                    ) ?>
                </td>

                <td class="numero">
                    <?= escaparExcelContabilistico(
                        moedaExcelContabilistico(
                            $resumo['total_passivo'] ?? 0
                        )
                    ) ?>
                </td>

                <td class="numero">
                    <?= escaparExcelContabilistico(
                        moedaExcelContabilistico(
                            $resumo['capital_resultado'] ?? 0
                        )
                    ) ?>
                </td>

                <td class="numero">
                    <?= escaparExcelContabilistico(
                        moedaExcelContabilistico(
                            $resumo['diferenca'] ?? 0
                        )
                    ) ?>
                </td>

            </tr>

        </table>


        <table>

            <thead>

                <tr>

                    <th>Código</th>
                    <th>Conta</th>
                    <th>Grupo</th>
                    <th>Valor</th>
                    <th>Situação</th>
                    <th>Observação</th>

                </tr>

            </thead>

            <tbody>


                <tr class="grupo">

                    <td colspan="6">
                        ATIVO
                    </td>

                </tr>


                <?php foreach ($ativos as $linha): ?>

                    <?php
                    $contrario =
                        !empty($linha['saldo_contrario']);
                    ?>

                    <tr
                        class="<?= $contrario
                                    ? 'alerta'
                                    : ''
                                ?>">

                        <td>
                            <?= escaparExcelContabilistico(
                                $linha['codigo']
                                    ?? '-'
                            ) ?>
                        </td>

                        <td>
                            <?= escaparExcelContabilistico(
                                $linha['nome']
                                    ?? '-'
                            ) ?>
                        </td>

                        <td>
                            ATIVO
                        </td>

                        <td class="numero">
                            <?= escaparExcelContabilistico(
                                moedaExcelContabilistico(
                                    $linha['valor']
                                        ?? 0
                                )
                            ) ?>
                        </td>

                        <td>
                            <?= $contrario
                                ? 'REVER'
                                : 'NORMAL'
                            ?>
                        </td>

                        <td>
                            <?= escaparExcelContabilistico(
                                $linha['alerta']
                                    ?? ''
                            ) ?>
                        </td>

                    </tr>

                <?php endforeach; ?>


                <tr class="total">

                    <td colspan="3">
                        TOTAL DO ATIVO
                    </td>

                    <td class="numero">
                        <?= escaparExcelContabilistico(
                            moedaExcelContabilistico(
                                $resumo['total_ativo'] ?? 0
                            )
                        ) ?>
                    </td>

                    <td colspan="2"></td>

                </tr>


                <tr class="grupo">

                    <td colspan="6">
                        PASSIVO
                    </td>

                </tr>


                <?php foreach ($passivos as $linha): ?>

                    <?php
                    $contrario =
                        !empty($linha['saldo_contrario']);
                    ?>

                    <tr
                        class="<?= $contrario
                                    ? 'alerta'
                                    : ''
                                ?>">

                        <td>
                            <?= escaparExcelContabilistico(
                                $linha['codigo']
                                    ?? '-'
                            ) ?>
                        </td>

                        <td>
                            <?= escaparExcelContabilistico(
                                $linha['nome']
                                    ?? '-'
                            ) ?>
                        </td>

                        <td>
                            PASSIVO
                        </td>

                        <td class="numero">
                            <?= escaparExcelContabilistico(
                                moedaExcelContabilistico(
                                    $linha['valor']
                                        ?? 0
                                )
                            ) ?>
                        </td>

                        <td>
                            <?= $contrario
                                ? 'REVER'
                                : 'NORMAL'
                            ?>
                        </td>

                        <td>
                            <?= escaparExcelContabilistico(
                                $linha['alerta']
                                    ?? ''
                            ) ?>
                        </td>

                    </tr>

                <?php endforeach; ?>


                <tr class="total">

                    <td colspan="3">
                        TOTAL DO PASSIVO
                    </td>

                    <td class="numero">
                        <?= escaparExcelContabilistico(
                            moedaExcelContabilistico(
                                $resumo['total_passivo'] ?? 0
                            )
                        ) ?>
                    </td>

                    <td colspan="2"></td>

                </tr>


                <tr class="grupo">

                    <td colspan="6">
                        CAPITAL PRÓPRIO
                    </td>

                </tr>


                <?php foreach ($capital as $linha): ?>

                    <?php
                    $contrario =
                        !empty($linha['saldo_contrario']);
                    ?>

                    <tr
                        class="<?= $contrario
                                    ? 'alerta'
                                    : ''
                                ?>">

                        <td>
                            <?= escaparExcelContabilistico(
                                $linha['codigo']
                                    ?? '-'
                            ) ?>
                        </td>

                        <td>
                            <?= escaparExcelContabilistico(
                                $linha['nome']
                                    ?? '-'
                            ) ?>
                        </td>

                        <td>
                            CAPITAL PRÓPRIO
                        </td>

                        <td class="numero">
                            <?= escaparExcelContabilistico(
                                moedaExcelContabilistico(
                                    $linha['valor']
                                        ?? 0
                                )
                            ) ?>
                        </td>

                        <td>
                            <?= $contrario
                                ? 'REVER'
                                : 'NORMAL'
                            ?>
                        </td>

                        <td>
                            <?= escaparExcelContabilistico(
                                $linha['alerta']
                                    ?? ''
                            ) ?>
                        </td>

                    </tr>

                <?php endforeach; ?>


                <tr>

                    <td>-</td>

                    <td>
                        Resultado do período
                    </td>

                    <td>
                        CAPITAL PRÓPRIO
                    </td>

                    <td class="numero">
                        <?= escaparExcelContabilistico(
                            moedaExcelContabilistico(
                                $resumo['resultado_periodo'] ?? 0
                            )
                        ) ?>
                    </td>

                    <td>NORMAL</td>

                    <td></td>

                </tr>


                <tr class="total">

                    <td colspan="3">
                        CAPITAL + RESULTADO
                    </td>

                    <td class="numero">
                        <?= escaparExcelContabilistico(
                            moedaExcelContabilistico(
                                $resumo['capital_resultado'] ?? 0
                            )
                        ) ?>
                    </td>

                    <td colspan="2"></td>

                </tr>


                <tr class="total">

                    <td colspan="3">
                        PASSIVO + CAPITAL PRÓPRIO
                    </td>

                    <td class="numero">
                        <?= escaparExcelContabilistico(
                            moedaExcelContabilistico(
                                $resumo['passivo_capital'] ?? 0
                            )
                        ) ?>
                    </td>

                    <td colspan="2"></td>

                </tr>


                <tr class="total">

                    <td colspan="3">
                        DIFERENÇA
                    </td>

                    <td class="numero">
                        <?= escaparExcelContabilistico(
                            moedaExcelContabilistico(
                                $resumo['diferenca'] ?? 0
                            )
                        ) ?>
                    </td>

                    <td colspan="2"></td>

                </tr>

            </tbody>

        </table>


    <?php

    /*
|--------------------------------------------------------------------------
| IVA
|--------------------------------------------------------------------------
*/

    elseif ($tipo === 'IVA'):

        $resumo =
            $dadosRelatorio['resumo']
            ?? [];

        $registos =
            $dadosRelatorio['registos']
            ?? [];

    ?>

        <table>

            <tr>

                <th>IVA Liquidado</th>
                <th>IVA Dedutível</th>
                <th>IVA a Pagar</th>
                <th>IVA a Recuperar</th>

            </tr>

            <tr>

                <td class="numero">
                    <?= escaparExcelContabilistico(
                        moedaExcelContabilistico(
                            $resumo['iva_liquidado'] ?? 0
                        )
                    ) ?>
                </td>

                <td class="numero">
                    <?= escaparExcelContabilistico(
                        moedaExcelContabilistico(
                            $resumo['iva_dedutivel'] ?? 0
                        )
                    ) ?>
                </td>

                <td class="numero">
                    <?= escaparExcelContabilistico(
                        moedaExcelContabilistico(
                            $resumo['iva_pagar'] ?? 0
                        )
                    ) ?>
                </td>

                <td class="numero">
                    <?= escaparExcelContabilistico(
                        moedaExcelContabilistico(
                            $resumo['iva_recuperar'] ?? 0
                        )
                    ) ?>
                </td>

            </tr>

        </table>


        <table>

            <thead>

                <tr>

                    <th>Data</th>
                    <th>Documento</th>
                    <th>Origem</th>
                    <th>Conta</th>
                    <th>Conta IVA</th>
                    <th>Tipo</th>
                    <th>Descrição</th>
                    <th>Débito</th>
                    <th>Crédito</th>

                </tr>

            </thead>

            <tbody>

                <?php if (empty($registos)): ?>

                    <tr>

                        <td
                            colspan="9"
                            style="text-align:center;">

                            Nenhum movimento de IVA encontrado.

                        </td>

                    </tr>

                <?php else: ?>

                    <?php foreach ($registos as $linha): ?>

                        <tr>

                            <td>
                                <?= escaparExcelContabilistico(
                                    dataExcelContabilistico(
                                        $linha['data_lancamento'] ?? ''
                                    )
                                ) ?>
                            </td>

                            <td>
                                <?= escaparExcelContabilistico(
                                    $linha['numero_documento']
                                        ?? $linha['referencia']
                                        ?? '-'
                                ) ?>
                            </td>

                            <td>
                                <?= escaparExcelContabilistico(
                                    $linha['origem']
                                        ?? '-'
                                ) ?>
                            </td>

                            <td>
                                <?= escaparExcelContabilistico(
                                    $linha['conta_codigo'] ?? '-'
                                ) ?>
                            </td>

                            <td>
                                <?= escaparExcelContabilistico(
                                    $linha['conta_nome'] ?? '-'
                                ) ?>
                            </td>

                            <td>
                                <?= escaparExcelContabilistico(
                                    $linha['tipo_iva'] ?? '-'
                                ) ?>
                            </td>

                            <td>
                                <?= escaparExcelContabilistico(
                                    $linha['descricao'] ?? '-'
                                ) ?>
                            </td>

                            <td class="numero">
                                <?= escaparExcelContabilistico(
                                    moedaExcelContabilistico(
                                        $linha['debito']
                                            ?? 0
                                    )
                                ) ?>
                            </td>

                            <td class="numero">
                                <?= escaparExcelContabilistico(
                                    moedaExcelContabilistico(
                                        $linha['credito']
                                            ?? 0
                                    )
                                ) ?>
                            </td>

                        </tr>

                    <?php endforeach; ?>

                <?php endif; ?>

            </tbody>

        </table>

    <?php endif; ?>


</body>

</html>