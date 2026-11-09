<?php

declare(strict_types=1);

if (
    session_status()
    !== PHP_SESSION_ACTIVE
) {
    session_start();
}


error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');


require_once __DIR__
    . '/../core/Database.php';

require_once __DIR__
    . '/../models/RelatorioContabilistico.php';

require_once __DIR__
    . '/../controllers/RelatorioContabilisticoController.php';


function eContabilistico(
    mixed $valor
): string {

    return htmlspecialchars(
        (string)$valor,
        ENT_QUOTES,
        'UTF-8'
    );
}


function moedaContabilistica(
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


function dataContabilistica(
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


function saldoContabilistico(
    mixed $devedor,
    mixed $credor
): string {

    $devedor =
        (float)$devedor;

    $credor =
        (float)$credor;


    if ($devedor > 0) {

        return moedaContabilistica(
            $devedor
        )
            . ' D';
    }


    if ($credor > 0) {

        return moedaContabilistica(
            $credor
        )
            . ' C';
    }


    return moedaContabilistica(0);
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

    http_response_code(401);

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


$permitidos = [

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
        $permitidos,
        true
    )
) {

    http_response_code(400);

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
            $resultado['message']
                ?? 'Não foi possível gerar o relatório.'
        );
    }


    $relatorio =
        $resultado['data']
        ?? [];
} catch (Throwable $e) {

    error_log(
        sprintf(
            '[relatorios_contabilisticos_imprimir] %s em %s:%d',
            $e->getMessage(),
            $e->getFile(),
            $e->getLine()
        )
    );


    http_response_code(500);

    exit(eContabilistico(
            $e->getMessage()
        ));
}


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


$resumo =
    $relatorio['resumo']
    ?? [];

?>
<!DOCTYPE html>

<html lang="pt">

<head>

    <meta charset="UTF-8">

    <title>
        <?= eContabilistico($titulo) ?>
    </title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 20px;
            font-family: Arial, Helvetica, sans-serif;
            color: #222;
            font-size: 11px;
        }

        .cabecalho {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 2px solid #222;
            padding-bottom: 12px;
            margin-bottom: 18px;
        }

        .titulo {
            font-size: 22px;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .subtitulo {
            font-size: 11px;
            color: #666;
        }

        .data-geracao {
            text-align: right;
            font-size: 10px;
        }

        .filtros {
            border: 1px solid #ddd;
            padding: 10px;
            margin-bottom: 15px;
            background: #fafafa;
        }

        .filtros strong {
            margin-right: 4px;
        }

        .resumo {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 18px;
        }

        .resumo th {
            background: #f0f0f0;
            border: 1px solid #ccc;
            padding: 7px;
            text-align: left;
        }

        .resumo td {
            border: 1px solid #ccc;
            padding: 8px;
            font-weight: bold;
        }

        table.relatorio {
            width: 100%;
            border-collapse: collapse;
        }

        table.relatorio th {
            border: 1px solid #bbb;
            background: #eaeaea;
            padding: 5px;
            text-align: left;
        }

        table.relatorio td {
            border: 1px solid #ccc;
            padding: 5px;
            vertical-align: top;
        }

        .numero {
            text-align: right !important;
            white-space: nowrap;
        }

        .centro {
            text-align: center !important;
        }

        .grupo {
            background: #ddd;
            font-weight: bold;
        }

        .total {
            background: #f5f5f5;
            font-weight: bold;
        }

        .alerta {
            background: #fff3cd;
        }

        .alerta-texto {
            font-size: 9px;
            margin-top: 3px;
        }

        .secao {
            margin-bottom: 20px;
        }

        .secao-titulo {
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 7px;
        }

        .balanco-grid {
            display: flex;
            gap: 15px;
        }

        .balanco-coluna {
            width: 50%;
        }

        .rodape {
            margin-top: 25px;
            padding-top: 8px;
            border-top: 1px solid #ccc;
            font-size: 9px;
            color: #666;
        }

        .acoes {
            margin-bottom: 15px;
        }

        .acoes button {
            padding: 8px 15px;
            cursor: pointer;
        }

        @media print {

            body {
                padding: 0;
            }

            .acoes {
                display: none;
            }

            .cabecalho {
                margin-top: 0;
            }

            tr {
                page-break-inside: avoid;
            }

            thead {
                display: table-header-group;
            }

            tfoot {
                display: table-footer-group;
            }
        }

        @page {
            size: A4 landscape;
            margin: 10mm;
        }
    </style>

</head>

<body>


    <div class="acoes">

        <button
            type="button"
            onclick="window.print();">

            Imprimir / Guardar PDF

        </button>

    </div>


    <div class="cabecalho">

        <div>

            <div class="titulo">
                <?= eContabilistico($titulo) ?>
            </div>

            <div class="subtitulo">
                ERP Plus - Relatórios Contabilísticos
            </div>

        </div>


        <div class="data-geracao">

            Gerado em<br>

            <strong>
                <?= date('d/m/Y H:i:s') ?>
            </strong>

        </div>

    </div>


    <div class="filtros">

        <?php if ($dados['data_inicio'] !== ''): ?>

            <strong>Data inicial:</strong>

            <?= eContabilistico(
                dataContabilistica(
                    $dados['data_inicio']
                )
            ) ?>

            &nbsp;&nbsp;

        <?php endif; ?>


        <?php if ($dados['data_fim'] !== ''): ?>

            <strong>
                <?= $tipo === 'BALANCO_PATRIMONIAL'
                    ? 'Data de referência:'
                    : 'Data final:'
                ?>
            </strong>

            <?= eContabilistico(
                dataContabilistica(
                    $dados['data_fim']
                )
            ) ?>

            &nbsp;&nbsp;

        <?php endif; ?>


        <?php if ($dados['pesquisa'] !== ''): ?>

            <strong>Pesquisa:</strong>

            <?= eContabilistico(
                $dados['pesquisa']
            ) ?>

        <?php endif; ?>

    </div>


    <?php if ($tipo === 'DIARIO'): ?>

        <?php

        $registos =
            $relatorio['registos']
            ?? [];

        ?>

        <table class="resumo">

            <tr>
                <th>Lançamentos</th>
                <th>Débitos</th>
                <th>Créditos</th>
                <th>Diferença</th>
            </tr>

            <tr>

                <td>
                    <?= (int)(
                        $resumo['total_lancamentos']
                        ?? 0
                    ) ?>
                </td>

                <td>
                    <?= eContabilistico(
                        moedaContabilistica(
                            $resumo['total_debito']
                                ?? 0
                        )
                    ) ?>
                </td>

                <td>
                    <?= eContabilistico(
                        moedaContabilistica(
                            $resumo['total_credito']
                                ?? 0
                        )
                    ) ?>
                </td>

                <td>
                    <?= eContabilistico(
                        moedaContabilistica(
                            $resumo['diferenca']
                                ?? 0
                        )
                    ) ?>
                </td>

            </tr>

        </table>


        <table class="relatorio">

            <thead>

                <tr>
                    <th>Data</th>
                    <th>Diário</th>
                    <th>N.º</th>
                    <th>Documento</th>
                    <th>Conta</th>
                    <th>Descrição da conta</th>
                    <th>Descrição</th>
                    <th>Entidade</th>
                    <th>Débito</th>
                    <th>Crédito</th>
                </tr>

            </thead>

            <tbody>

                <?php foreach ($registos as $linha): ?>

                    <tr>

                        <td>
                            <?= eContabilistico(
                                dataContabilistica(
                                    $linha['data_lancamento']
                                        ?? ''
                                )
                            ) ?>
                        </td>

                        <td>
                            <?= eContabilistico(
                                $linha['diario_codigo']
                                    ?? '-'
                            ) ?>
                        </td>

                        <td>
                            <?= eContabilistico(
                                $linha['numero']
                                    ?? '-'
                            ) ?>
                        </td>

                        <td>
                            <?= eContabilistico(
                                $linha['numero_documento']
                                    ?? $linha['referencia']
                                    ?? '-'
                            ) ?>
                        </td>

                        <td>
                            <?= eContabilistico(
                                $linha['conta_codigo']
                                    ?? '-'
                            ) ?>
                        </td>

                        <td>
                            <?= eContabilistico(
                                $linha['conta_nome']
                                    ?? '-'
                            ) ?>
                        </td>

                        <td>
                            <?= eContabilistico(
                                $linha['item_descricao']
                                    ?? '-'
                            ) ?>
                        </td>

                        <td>
                            <?= eContabilistico(
                                $linha['entidade']
                                    ?? '-'
                            ) ?>
                        </td>

                        <td class="numero">
                            <?= eContabilistico(
                                moedaContabilistica(
                                    $linha['debito']
                                        ?? 0
                                )
                            ) ?>
                        </td>

                        <td class="numero">
                            <?= eContabilistico(
                                moedaContabilistica(
                                    $linha['credito']
                                        ?? 0
                                )
                            ) ?>
                        </td>

                    </tr>

                <?php endforeach; ?>

            </tbody>

        </table>


    <?php elseif ($tipo === 'RAZAO'): ?>

        <?php

        $conta =
            $relatorio['conta']
            ?? [];

        $registos =
            $relatorio['registos']
            ?? [];

        ?>

        <div class="secao">

            <div class="secao-titulo">

                <?= eContabilistico(
                    ($conta['codigo'] ?? '')
                        . ' - '
                        . ($conta['nome'] ?? '')
                ) ?>

            </div>

        </div>


        <table class="resumo">

            <tr>
                <th>Saldo inicial</th>
                <th>Débitos</th>
                <th>Créditos</th>
                <th>Saldo final</th>
            </tr>

            <tr>

                <td>
                    <?= eContabilistico(
                        saldoContabilistico(
                            $resumo['saldo_inicial_devedor'] ?? 0,

                            $resumo['saldo_inicial_credor'] ?? 0
                        )
                    ) ?>
                </td>

                <td>
                    <?= eContabilistico(
                        moedaContabilistica(
                            $resumo['total_debitos'] ?? 0
                        )
                    ) ?>
                </td>

                <td>
                    <?= eContabilistico(
                        moedaContabilistica(
                            $resumo['total_creditos'] ?? 0
                        )
                    ) ?>
                </td>

                <td>
                    <?= eContabilistico(
                        saldoContabilistico(
                            $resumo['saldo_final_devedor'] ?? 0,

                            $resumo['saldo_final_credor'] ?? 0
                        )
                    ) ?>
                </td>

            </tr>

        </table>


        <table class="relatorio">

            <thead>

                <tr>
                    <th>Data</th>
                    <th>Diário</th>
                    <th>Documento</th>
                    <th>Descrição</th>
                    <th>Origem</th>
                    <th>Débito</th>
                    <th>Crédito</th>
                    <th>Saldo</th>
                </tr>

            </thead>

            <tbody>

                <?php foreach ($registos as $linha): ?>

                    <tr>

                        <td>
                            <?= eContabilistico(
                                dataContabilistica(
                                    $linha['data_lancamento']
                                        ?? ''
                                )
                            ) ?>
                        </td>

                        <td>
                            <?= eContabilistico(
                                $linha['diario_codigo']
                                    ?? '-'
                            ) ?>
                        </td>

                        <td>
                            <?= eContabilistico(
                                $linha['numero_documento']
                                    ?? $linha['referencia']
                                    ?? '-'
                            ) ?>
                        </td>

                        <td>
                            <?= eContabilistico(
                                $linha['descricao']
                                    ?? '-'
                            ) ?>
                        </td>

                        <td>
                            <?= eContabilistico(
                                $linha['origem']
                                    ?? '-'
                            ) ?>
                        </td>

                        <td class="numero">
                            <?= eContabilistico(
                                moedaContabilistica(
                                    $linha['debito']
                                        ?? 0
                                )
                            ) ?>
                        </td>

                        <td class="numero">
                            <?= eContabilistico(
                                moedaContabilistica(
                                    $linha['credito']
                                        ?? 0
                                )
                            ) ?>
                        </td>

                        <td class="numero">
                            <?= eContabilistico(
                                saldoContabilistico(
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


    <?php elseif ($tipo === 'BALANCETE'): ?>

        <?php

        $registos =
            $relatorio['registos']
            ?? [];

        ?>

        <table class="resumo">

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

                <td>
                    <?= eContabilistico(
                        moedaContabilistica(
                            $resumo['total_debito']
                                ?? 0
                        )
                    ) ?>
                </td>

                <td>
                    <?= eContabilistico(
                        moedaContabilistica(
                            $resumo['total_credito']
                                ?? 0
                        )
                    ) ?>
                </td>

                <td>
                    <?= eContabilistico(
                        moedaContabilistica(
                            $resumo['diferenca']
                                ?? 0
                        )
                    ) ?>
                </td>

            </tr>

        </table>


        <table class="relatorio">

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
                            <?= eContabilistico(
                                $linha['codigo']
                                    ?? '-'
                            ) ?>
                        </td>

                        <td>
                            <?= eContabilistico(
                                $linha['nome']
                                    ?? '-'
                            ) ?>
                        </td>

                        <td>
                            <?= eContabilistico(
                                $linha['grupo']
                                    ?? '-'
                            ) ?>
                        </td>

                        <td>
                            <?= eContabilistico(
                                $linha['natureza']
                                    ?? '-'
                            ) ?>
                        </td>

                        <td class="numero">
                            <?= eContabilistico(
                                moedaContabilistica(
                                    $linha['debito']
                                        ?? 0
                                )
                            ) ?>
                        </td>

                        <td class="numero">
                            <?= eContabilistico(
                                moedaContabilistica(
                                    $linha['credito']
                                        ?? 0
                                )
                            ) ?>
                        </td>

                        <td class="numero">
                            <?= eContabilistico(
                                moedaContabilistica(
                                    $linha['saldo_devedor']
                                        ?? 0
                                )
                            ) ?>
                        </td>

                        <td class="numero">
                            <?= eContabilistico(
                                moedaContabilistica(
                                    $linha['saldo_credor']
                                        ?? 0
                                )
                            ) ?>
                        </td>

                    </tr>

                <?php endforeach; ?>

            </tbody>

        </table>


    <?php elseif ($tipo === 'DRE'): ?>

        <?php

        $rendimentos =
            $relatorio['rendimentos']
            ?? [];

        $gastos =
            $relatorio['gastos']
            ?? [];

        ?>

        <table class="resumo">

            <tr>
                <th>Rendimentos</th>
                <th>Gastos</th>
                <th>Resultado Líquido</th>
                <th>Margem</th>
            </tr>

            <tr>

                <td>
                    <?= eContabilistico(
                        moedaContabilistica(
                            $resumo['total_rendimentos'] ?? 0
                        )
                    ) ?>
                </td>

                <td>
                    <?= eContabilistico(
                        moedaContabilistica(
                            $resumo['total_gastos'] ?? 0
                        )
                    ) ?>
                </td>

                <td>
                    <?= eContabilistico(
                        moedaContabilistica(
                            $resumo['resultado_liquido'] ?? 0
                        )
                    ) ?>
                </td>

                <td>
                    <?= eContabilistico(
                        number_format(
                            (float)(
                                $resumo['margem']
                                ?? 0
                            ),
                            2,
                            ',',
                            ' '
                        )
                            . '%'
                    ) ?>
                </td>

            </tr>

        </table>


        <table class="relatorio">

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
                            <?= eContabilistico(
                                $linha['codigo']
                                    ?? '-'
                            ) ?>
                        </td>

                        <td>
                            <?= eContabilistico(
                                $linha['nome']
                                    ?? '-'
                            ) ?>
                        </td>

                        <td class="numero">
                            <?= eContabilistico(
                                moedaContabilistica(
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
                            <?= eContabilistico(
                                $linha['codigo']
                                    ?? '-'
                            ) ?>
                        </td>

                        <td>
                            <?= eContabilistico(
                                $linha['nome']
                                    ?? '-'
                            ) ?>
                        </td>

                        <td class="numero">
                            <?= eContabilistico(
                                moedaContabilistica(
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

                        <?= eContabilistico(
                            moedaContabilistica(
                                $resumo['resultado_liquido'] ?? 0
                            )
                        ) ?>

                    </td>

                </tr>

            </tbody>

        </table>


    <?php elseif (
        $tipo === 'BALANCO_PATRIMONIAL'
    ): ?>

        <?php

        $ativos =
            $relatorio['ativos']
            ?? [];

        $passivos =
            $relatorio['passivos']
            ?? [];

        $capital =
            $relatorio['capital_proprio']
            ?? [];

        ?>

        <table class="resumo">

            <tr>
                <th>Ativo</th>
                <th>Passivo</th>
                <th>Capital + Resultado</th>
                <th>Diferença</th>
            </tr>

            <tr>

                <td>
                    <?= eContabilistico(
                        moedaContabilistica(
                            $resumo['total_ativo']
                                ?? 0
                        )
                    ) ?>
                </td>

                <td>
                    <?= eContabilistico(
                        moedaContabilistica(
                            $resumo['total_passivo']
                                ?? 0
                        )
                    ) ?>
                </td>

                <td>
                    <?= eContabilistico(
                        moedaContabilistica(
                            $resumo['capital_resultado']
                                ?? 0
                        )
                    ) ?>
                </td>

                <td>
                    <?= eContabilistico(
                        moedaContabilistica(
                            $resumo['diferenca']
                                ?? 0
                        )
                    ) ?>
                </td>

            </tr>

        </table>


        <div class="balanco-grid">

            <div class="balanco-coluna">

                <div class="secao-titulo">
                    ATIVO
                </div>


                <table class="relatorio">

                    <thead>

                        <tr>
                            <th>Código</th>
                            <th>Conta</th>
                            <th>Valor</th>
                            <th>Situação</th>
                        </tr>

                    </thead>

                    <tbody>

                        <?php foreach ($ativos as $linha): ?>

                            <?php
                            $alerta =
                                !empty($linha['saldo_contrario']);
                            ?>

                            <tr
                                class="<?= $alerta
                                            ? 'alerta'
                                            : ''
                                        ?>">

                                <td>
                                    <?= eContabilistico(
                                        $linha['codigo']
                                            ?? '-'
                                    ) ?>
                                </td>

                                <td>

                                    <?= eContabilistico(
                                        $linha['nome']
                                            ?? '-'
                                    ) ?>


                                    <?php if ($alerta): ?>

                                        <div class="alerta-texto">

                                            <?= eContabilistico(
                                                $linha['alerta']
                                                    ?? ''
                                            ) ?>

                                        </div>

                                    <?php endif; ?>

                                </td>

                                <td class="numero">

                                    <?= eContabilistico(
                                        moedaContabilistica(
                                            $linha['valor']
                                                ?? 0
                                        )
                                    ) ?>

                                </td>

                                <td>

                                    <?= $alerta
                                        ? 'REVER'
                                        : 'NORMAL'
                                    ?>

                                </td>

                            </tr>

                        <?php endforeach; ?>


                        <tr class="total">

                            <td colspan="2">
                                Total do Ativo
                            </td>

                            <td class="numero">

                                <?= eContabilistico(
                                    moedaContabilistica(
                                        $resumo['total_ativo']
                                            ?? 0
                                    )
                                ) ?>

                            </td>

                            <td></td>

                        </tr>

                    </tbody>

                </table>

            </div>


            <div class="balanco-coluna">

                <div class="secao-titulo">
                    PASSIVO
                </div>


                <table class="relatorio">

                    <thead>

                        <tr>
                            <th>Código</th>
                            <th>Conta</th>
                            <th>Valor</th>
                            <th>Situação</th>
                        </tr>

                    </thead>

                    <tbody>

                        <?php foreach ($passivos as $linha): ?>

                            <?php
                            $alerta =
                                !empty($linha['saldo_contrario']);
                            ?>

                            <tr
                                class="<?= $alerta
                                            ? 'alerta'
                                            : ''
                                        ?>">

                                <td>
                                    <?= eContabilistico(
                                        $linha['codigo']
                                            ?? '-'
                                    ) ?>
                                </td>

                                <td>
                                    <?= eContabilistico(
                                        $linha['nome']
                                            ?? '-'
                                    ) ?>
                                </td>

                                <td class="numero">

                                    <?= eContabilistico(
                                        moedaContabilistica(
                                            $linha['valor']
                                                ?? 0
                                        )
                                    ) ?>

                                </td>

                                <td>

                                    <?= $alerta
                                        ? 'REVER'
                                        : 'NORMAL'
                                    ?>

                                </td>

                            </tr>

                        <?php endforeach; ?>


                        <tr class="total">

                            <td colspan="2">
                                Total do Passivo
                            </td>

                            <td class="numero">

                                <?= eContabilistico(
                                    moedaContabilistica(
                                        $resumo['total_passivo'] ?? 0
                                    )
                                ) ?>

                            </td>

                            <td></td>

                        </tr>

                    </tbody>

                </table>


                <br>


                <div class="secao-titulo">
                    CAPITAL PRÓPRIO
                </div>


                <table class="relatorio">

                    <thead>

                        <tr>
                            <th>Código</th>
                            <th>Conta</th>
                            <th>Valor</th>
                        </tr>

                    </thead>

                    <tbody>

                        <?php foreach ($capital as $linha): ?>

                            <tr>

                                <td>
                                    <?= eContabilistico(
                                        $linha['codigo']
                                            ?? '-'
                                    ) ?>
                                </td>

                                <td>
                                    <?= eContabilistico(
                                        $linha['nome']
                                            ?? '-'
                                    ) ?>
                                </td>

                                <td class="numero">

                                    <?= eContabilistico(
                                        moedaContabilistica(
                                            $linha['valor']
                                                ?? 0
                                        )
                                    ) ?>

                                </td>

                            </tr>

                        <?php endforeach; ?>


                        <tr>

                            <td>-</td>

                            <td>
                                Resultado do período
                            </td>

                            <td class="numero">

                                <?= eContabilistico(
                                    moedaContabilistica(
                                        $resumo['resultado_periodo'] ?? 0
                                    )
                                ) ?>

                            </td>

                        </tr>


                        <tr class="total">

                            <td colspan="2">
                                Capital + Resultado
                            </td>

                            <td class="numero">

                                <?= eContabilistico(
                                    moedaContabilistica(
                                        $resumo['capital_resultado'] ?? 0
                                    )
                                ) ?>

                            </td>

                        </tr>


                        <tr class="total">

                            <td colspan="2">
                                Passivo + Capital Próprio
                            </td>

                            <td class="numero">

                                <?= eContabilistico(
                                    moedaContabilistica(
                                        $resumo['passivo_capital'] ?? 0
                                    )
                                ) ?>

                            </td>

                        </tr>

                    </tbody>

                </table>

            </div>

        </div>


    <?php elseif ($tipo === 'IVA'): ?>

        <?php

        $registos =
            $relatorio['registos']
            ?? [];

        ?>

        <table class="resumo">

            <tr>
                <th>IVA Liquidado</th>
                <th>IVA Dedutível</th>
                <th>IVA a Pagar</th>
                <th>IVA a Recuperar</th>
            </tr>

            <tr>

                <td>
                    <?= eContabilistico(
                        moedaContabilistica(
                            $resumo['iva_liquidado']
                                ?? 0
                        )
                    ) ?>
                </td>

                <td>
                    <?= eContabilistico(
                        moedaContabilistica(
                            $resumo['iva_dedutivel']
                                ?? 0
                        )
                    ) ?>
                </td>

                <td>
                    <?= eContabilistico(
                        moedaContabilistica(
                            $resumo['iva_pagar']
                                ?? 0
                        )
                    ) ?>
                </td>

                <td>
                    <?= eContabilistico(
                        moedaContabilistica(
                            $resumo['iva_recuperar']
                                ?? 0
                        )
                    ) ?>
                </td>

            </tr>

        </table>


        <table class="relatorio">

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
                            class="centro">

                            Nenhum movimento de IVA encontrado.

                        </td>

                    </tr>

                <?php else: ?>

                    <?php foreach ($registos as $linha): ?>

                        <tr>

                            <td>
                                <?= eContabilistico(
                                    dataContabilistica(
                                        $linha['data_lancamento']
                                            ?? ''
                                    )
                                ) ?>
                            </td>

                            <td>
                                <?= eContabilistico(
                                    $linha['numero_documento']
                                        ?? $linha['referencia']
                                        ?? '-'
                                ) ?>
                            </td>

                            <td>
                                <?= eContabilistico(
                                    $linha['origem']
                                        ?? '-'
                                ) ?>
                            </td>

                            <td>
                                <?= eContabilistico(
                                    $linha['conta_codigo']
                                        ?? '-'
                                ) ?>
                            </td>

                            <td>
                                <?= eContabilistico(
                                    $linha['conta_nome']
                                        ?? '-'
                                ) ?>
                            </td>

                            <td>
                                <?= eContabilistico(
                                    $linha['tipo_iva']
                                        ?? '-'
                                ) ?>
                            </td>

                            <td>
                                <?= eContabilistico(
                                    $linha['descricao']
                                        ?? '-'
                                ) ?>
                            </td>

                            <td class="numero">
                                <?= eContabilistico(
                                    moedaContabilistica(
                                        $linha['debito']
                                            ?? 0
                                    )
                                ) ?>
                            </td>

                            <td class="numero">
                                <?= eContabilistico(
                                    moedaContabilistica(
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


    <div class="rodape">

        Documento gerado pelo ERP Plus.

    </div>


    <script>
        /*
    |--------------------------------------------------------------------------
    | Imprimir automaticamente
    |--------------------------------------------------------------------------
    |
    | Se preferires abrir primeiro a pré-visualização,
    | remove este bloco.
    |--------------------------------------------------------------------------
    */

        /*
        window.addEventListener(
            'load',
            function () {
                window.print();
            }
        );
        */
    </script>


</body>

</html>