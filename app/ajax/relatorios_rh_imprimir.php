<?php

declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../models/RelatorioRH.php';
require_once __DIR__ . '/../controllers/RelatorioRHController.php';

function eRH(mixed $valor): string
{
    return htmlspecialchars((string)$valor, ENT_QUOTES, 'UTF-8');
}

function dataRH(mixed $valor): string
{
    $valor = trim((string)$valor);
    if ($valor === '') {
        return '-';
    }

    $timestamp = strtotime($valor);
    return $timestamp === false ? eRH($valor) : date('d/m/Y', $timestamp);
}

function horaRH(mixed $valor): string
{
    $valor = trim((string)$valor);
    return $valor === '' ? '-' : substr($valor, 0, 5);
}

function moedaRH(mixed $valor): string
{
    return number_format((float)$valor, 2, ',', ' ') . ' MT';
}

function numeroRH(mixed $valor, int $decimais = 0): string
{
    return number_format((float)$valor, $decimais, ',', ' ');
}

function minutosRH(mixed $valor): string
{
    $minutos = max(0, (int)$valor);
    $horas = intdiv($minutos, 60);
    $resto = $minutos % 60;

    if ($horas > 0) {
        return sprintf('%dh %02dmin', $horas, $resto);
    }

    return $resto . ' min';
}

function textoCodigoRH(mixed $valor): string
{
    $valor = trim((string)$valor);
    if ($valor === '') {
        return '-';
    }

    return mb_convert_case(
        str_replace('_', ' ', mb_strtolower($valor)),
        MB_CASE_TITLE,
        'UTF-8'
    );
}

function simNaoRH(mixed $valor): string
{
    return (int)$valor === 1 ? 'Sim' : 'Não';
}

function funcionarioRH(array $linha): string
{
    $numero = trim((string)($linha['numero_funcionario'] ?? ''));
    $nome = trim((string)($linha['funcionario_nome'] ?? $linha['nome_completo'] ?? ''));

    return trim(($numero !== '' ? $numero . ' - ' : '') . ($nome !== '' ? $nome : '-'));
}

function valorLinhaRH(array $linha, string $campo, string $formato = 'texto'): string
{
    $valor = $linha[$campo] ?? null;

    return match ($formato) {
        'data' => dataRH($valor),
        'hora' => horaRH($valor),
        'moeda' => moedaRH($valor),
        'minutos' => minutosRH($valor),
        'numero' => numeroRH($valor),
        'decimal' => numeroRH($valor, 2),
        'percentagem' => numeroRH($valor, 2) . '%',
        'codigo' => textoCodigoRH($valor),
        'sim_nao' => simNaoRH($valor),
        'funcionario' => funcionarioRH($linha),
        default => trim((string)$valor) !== '' ? (string)$valor : '-'
    };
}

$empresaId = (int)(
    $_SESSION['empresa_id']
    ?? $_SESSION['usuario']['empresa_id']
    ?? $_SESSION['user']['empresa_id']
    ?? 0
);

if ($empresaId <= 0) {
    http_response_code(401);
    exit('Empresa não identificada na sessão.');
}

$tipo = strtoupper(trim((string)($_GET['tipo'] ?? '')));
$tiposPermitidos = [
    'ASSIDUIDADE',
    'FALTAS',
    'HORAS_EXTRAS',
    'FOLHA_SALARIAL',
    'ENCARGOS',
    'FUNCIONARIOS',
    'PERFIL_FUNCIONARIO'
];

if (!in_array($tipo, $tiposPermitidos, true)) {
    http_response_code(400);
    exit('Tipo de relatório de RH inválido.');
}

$dados = [
    'tipo' => $tipo,
    'data_inicio' => trim((string)($_GET['data_inicio'] ?? '')),
    'data_fim' => trim((string)($_GET['data_fim'] ?? '')),
    'periodo_id' => $_GET['periodo_id'] ?? '',
    'filial_id' => $_GET['filial_id'] ?? '',
    'departamento_id' => $_GET['departamento_id'] ?? '',
    'cargo_id' => $_GET['cargo_id'] ?? '',
    'funcionario_id' => $_GET['funcionario_id'] ?? '',
    'ocorrencia' => trim((string)($_GET['ocorrencia'] ?? '')),
    'tipo_falta' => trim((string)($_GET['tipo_falta'] ?? '')),
    'tipo_hora_extra' => trim((string)($_GET['tipo_hora_extra'] ?? '')),
    'origem' => trim((string)($_GET['origem'] ?? '')),
    'sexo' => trim((string)($_GET['sexo'] ?? '')),
    'tipo_vinculo' => trim((string)($_GET['tipo_vinculo'] ?? '')),
    'pesquisa' => trim((string)($_GET['pesquisa'] ?? ''))
];

try {
    $db = Database::conn();
    $controller = new RelatorioRHController($db);
    $resultado = $controller->relatorio($empresaId, $dados);

    if (empty($resultado['success'])) {
        throw new RuntimeException(
            $resultado['message'] ?? 'Não foi possível gerar o relatório de RH.'
        );
    }

    $relatorio = is_array($resultado['data'] ?? null)
        ? $resultado['data']
        : [];

    $empresaNome = 'Empresa #' . $empresaId;
    $stmtEmpresa = $db->prepare('SELECT nome FROM empresas WHERE id = :id LIMIT 1');
    $stmtEmpresa->execute([':id' => $empresaId]);
    $nomeEncontrado = $stmtEmpresa->fetchColumn();
    if (is_string($nomeEncontrado) && trim($nomeEncontrado) !== '') {
        $empresaNome = $nomeEncontrado;
    }
} catch (Throwable $e) {
    error_log(sprintf(
        '[relatorios_rh_imprimir] %s em %s:%d',
        $e->getMessage(),
        $e->getFile(),
        $e->getLine()
    ));
    http_response_code(500);
    exit(eRH($e->getMessage()));
}

$titulos = [
    'ASSIDUIDADE' => 'Relatório de Assiduidade',
    'FALTAS' => 'Relatório de Faltas',
    'HORAS_EXTRAS' => 'Relatório de Horas Extras',
    'FOLHA_SALARIAL' => 'Relatório da Folha Salarial',
    'ENCARGOS' => 'Relatório de Encargos',
    'FUNCIONARIOS' => 'Relatório de Funcionários',
    'PERFIL_FUNCIONARIO' => 'Perfil do Funcionário'
];

$configuracoes = [
    'ASSIDUIDADE' => [
        'resumo' => [
            ['Presenças', 'presencas', 'numero'],
            ['Faltas', 'faltas', 'numero'],
            ['Atrasos', 'atrasos', 'numero'],
            ['Tempo trabalhado', 'minutos_trabalhados', 'minutos']
        ],
        'colunas' => [
            ['Data', 'data_assiduidade', 'data'],
            ['Funcionário', '', 'funcionario'],
            ['Filial', 'filial_nome', 'texto'],
            ['Departamento', 'departamento_nome', 'texto'],
            ['Entrada', 'hora_real_entrada', 'hora'],
            ['Saída', 'hora_real_saida', 'hora'],
            ['Trabalhado', 'minutos_trabalhados', 'minutos'],
            ['Atraso', 'minutos_atraso', 'minutos'],
            ['Extra', 'minutos_extra', 'minutos'],
            ['Ocorrência', 'ocorrencia', 'codigo']
        ]
    ],
    'FALTAS' => [
        'resumo' => [
            ['Faltas', 'total_faltas', 'numero'],
            ['Total de dias', 'total_dias', 'decimal'],
            ['Duração', 'total_minutos', 'minutos'],
            ['Descontam salário', 'descontam_salario', 'numero']
        ],
        'colunas' => [
            ['Início', 'data_inicio', 'data'],
            ['Fim', 'data_fim', 'data'],
            ['Funcionário', '', 'funcionario'],
            ['Tipo', 'tipo_falta', 'codigo'],
            ['Dias', 'quantidade_dias', 'decimal'],
            ['Duração', 'minutos_falta', 'minutos'],
            ['Remunerada', 'remunerada', 'sim_nao'],
            ['Desconta salário', 'desconta_salario', 'sim_nao'],
            ['Motivo', 'motivo', 'texto'],
            ['Estado', 'estado', 'texto']
        ]
    ],
    'HORAS_EXTRAS' => [
        'resumo' => [
            ['Registos', 'total_registos', 'numero'],
            ['Horas apuradas', 'minutos_apurados', 'minutos'],
            ['Horas aprovadas', 'minutos_aprovados', 'minutos'],
            ['Valor total', 'valor_total', 'moeda']
        ],
        'colunas' => [
            ['Data', 'data_hora_extra', 'data'],
            ['Funcionário', '', 'funcionario'],
            ['Tipo', 'tipo_hora_extra', 'codigo'],
            ['Início', 'hora_inicio', 'hora'],
            ['Fim', 'hora_fim', 'hora'],
            ['Apuradas', 'minutos_apurados', 'minutos'],
            ['Aprovadas', 'minutos_aprovados', 'minutos'],
            ['Acréscimo', 'percentual_acrescimo', 'percentagem'],
            ['Valor', 'valor_total', 'moeda'],
            ['Estado', 'estado', 'texto']
        ]
    ],
    'FOLHA_SALARIAL' => [
        'resumo' => [
            ['Funcionários', 'numero_funcionarios', 'numero'],
            ['Total bruto', 'total_bruto', 'moeda'],
            ['Total descontos', 'total_descontos', 'moeda'],
            ['Total líquido', 'total_liquido', 'moeda']
        ],
        'colunas' => [
            ['Período', 'periodo_descricao', 'texto'],
            ['Código', 'codigo', 'texto'],
            ['Funcionários', 'numero_funcionarios', 'numero'],
            ['Salário base', 'total_salario_base', 'moeda'],
            ['Proventos', 'total_proventos', 'moeda'],
            ['Descontos', 'total_descontos', 'moeda'],
            ['Bruto', 'total_bruto', 'moeda'],
            ['Líquido', 'total_liquido', 'moeda'],
            ['Pago', 'total_pago', 'moeda'],
            ['Pendente', 'saldo_pendente', 'moeda'],
            ['Estado', 'estado', 'texto']
        ]
    ],
    'ENCARGOS' => [
        'resumo' => [
            ['INSS trabalhador', 'total_inss_trabalhador', 'moeda'],
            ['INSS patronal', 'total_inss_patronal', 'moeda'],
            ['IRPS', 'total_irps', 'moeda'],
            ['Encargos fiscais/sociais', 'encargos_fiscais_sociais', 'moeda']
        ],
        'colunas' => [
            ['Período', 'periodo_descricao', 'texto'],
            ['Funcionários', 'numero_funcionarios', 'numero'],
            ['INSS trabalhador', 'total_inss', 'moeda'],
            ['INSS patronal', 'total_inss_patronal', 'moeda'],
            ['IRPS', 'total_irps', 'moeda'],
            ['Outros descontos', 'total_outros_descontos', 'moeda'],
            ['Retenções', 'retencoes_trabalhador', 'moeda'],
            ['Encargos fiscais/sociais', 'encargos_fiscais_sociais', 'moeda'],
            ['Estado', 'estado', 'texto']
        ]
    ],
    'FUNCIONARIOS' => [
        'resumo' => [
            ['Funcionários', 'total_funcionarios', 'numero'],
            ['Em quadro', 'em_quadro', 'numero'],
            ['Feminino', 'feminino', 'numero'],
            ['Massa salarial base', 'massa_salarial_base', 'moeda']
        ],
        'colunas' => [
            ['Número', 'numero_funcionario', 'texto'],
            ['Funcionário', 'nome_completo', 'texto'],
            ['Sexo', 'sexo', 'codigo'],
            ['Filial', 'filial_nome', 'texto'],
            ['Departamento', 'departamento_nome', 'texto'],
            ['Cargo', 'cargo_nome', 'texto'],
            ['Vínculo', 'tipo_vinculo', 'codigo'],
            ['Admissão', 'data_admissao', 'data'],
            ['Salário base', 'salario_base_atual', 'moeda'],
            ['Estado', 'estado', 'texto']
        ]
    ]
];

$titulo = $titulos[$tipo];
$resumo = is_array($relatorio['resumo'] ?? null) ? $relatorio['resumo'] : [];
$registos = is_array($relatorio['registos'] ?? null) ? $relatorio['registos'] : [];
$config = $configuracoes[$tipo] ?? null;

$filtrosVisiveis = [];
if ($dados['data_inicio'] !== '') $filtrosVisiveis['Data inicial'] = dataRH($dados['data_inicio']);
if ($dados['data_fim'] !== '') $filtrosVisiveis['Data final'] = dataRH($dados['data_fim']);
if ($dados['pesquisa'] !== '') $filtrosVisiveis['Pesquisa'] = $dados['pesquisa'];
foreach (['ocorrencia' => 'Ocorrência', 'tipo_falta' => 'Tipo de falta', 'tipo_hora_extra' => 'Tipo de hora extra', 'origem' => 'Origem', 'sexo' => 'Sexo', 'tipo_vinculo' => 'Vínculo'] as $campo => $rotulo) {
    if ($dados[$campo] !== '') $filtrosVisiveis[$rotulo] = textoCodigoRH($dados[$campo]);
}

?>
<!DOCTYPE html>
<html lang="pt">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= eRH($titulo) ?></title>
    <style>
        * {
            box-sizing: border-box
        }

        body {
            margin: 0;
            padding: 18px;
            font: 10px Arial, Helvetica, sans-serif;
            color: #222
        }

        .acoes {
            margin-bottom: 14px
        }

        .acoes button {
            padding: 8px 15px;
            cursor: pointer
        }

        .cabecalho {
            display: flex;
            justify-content: space-between;
            border-bottom: 2px solid #222;
            padding-bottom: 10px;
            margin-bottom: 14px
        }

        h1 {
            font-size: 20px;
            margin: 0 0 4px
        }

        .empresa {
            font-size: 12px;
            font-weight: bold
        }

        .subtitulo,
        .gerado,
        .rodape {
            color: #666
        }

        .gerado {
            text-align: right
        }

        .filtros {
            border: 1px solid #ddd;
            background: #fafafa;
            padding: 8px;
            margin-bottom: 12px
        }

        .filtro {
            display: inline-block;
            margin-right: 18px
        }

        .resumo {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 8px;
            margin-bottom: 14px
        }

        .resumo-item {
            border: 1px solid #ccc;
            padding: 8px
        }

        .resumo-item span {
            display: block;
            color: #666;
            margin-bottom: 3px
        }

        .resumo-item strong {
            font-size: 13px
        }

        table {
            width: 100%;
            border-collapse: collapse
        }

        th {
            background: #eaeaea;
            text-align: left
        }

        th,
        td {
            border: 1px solid #bbb;
            padding: 5px;
            vertical-align: top
        }

        tr {
            page-break-inside: avoid
        }

        .vazio {
            text-align: center;
            padding: 18px;
            color: #666
        }

        .perfil-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 8px;
            margin-bottom: 14px
        }

        .perfil-card {
            border: 1px solid #ccc;
            padding: 9px
        }

        .perfil-card h2 {
            grid-column: 1/-1;
            font-size: 15px;
            margin: 0 0 8px
        }

        .perfil-card span {
            display: block;
            color: #666;
            margin-bottom: 3px
        }

        .perfil-card strong {
            font-size: 12px
        }

        .perfil-largo {
            grid-column: span 2
        }

        .rodape {
            margin-top: 20px;
            padding-top: 7px;
            border-top: 1px solid #ccc;
            font-size: 9px
        }

        @media print {
            body {
                padding: 0
            }

            .acoes {
                display: none
            }

            thead {
                display: table-header-group
            }

            .cabecalho {
                margin-top: 0
            }
        }

        @page {
            size: A4 landscape;
            margin: 9mm
        }
    </style>
</head>

<body>
    <div class="acoes"><button type="button" onclick="window.print()">Imprimir / Guardar PDF</button></div>
    <header class="cabecalho">
        <div>
            <h1><?= eRH($titulo) ?></h1>
            <div class="empresa"><?= eRH($empresaNome) ?></div>
            <div class="subtitulo">ERP Plus — Recursos Humanos</div>
        </div>
        <div class="gerado">Gerado em<br><strong><?= date('d/m/Y H:i:s') ?></strong></div>
    </header>

    <?php if ($filtrosVisiveis !== []): ?>
        <section class="filtros">
            <?php foreach ($filtrosVisiveis as $rotulo => $valor): ?>
                <span class="filtro"><strong><?= eRH($rotulo) ?>:</strong> <?= eRH($valor) ?></span>
            <?php endforeach; ?>
        </section>
    <?php endif; ?>

    <?php if ($tipo === 'PERFIL_FUNCIONARIO'): ?>
        <?php
        $funcionario = is_array($relatorio['funcionario'] ?? null) ? $relatorio['funcionario'] : [];
        $contrato = is_array($relatorio['contrato'] ?? null) ? $relatorio['contrato'] : [];
        $salario = is_array($relatorio['salario'] ?? null) ? $relatorio['salario'] : [];
        $assiduidade = $relatorio['assiduidade']['resumo'] ?? [];
        $faltas = $relatorio['faltas']['resumo'] ?? [];
        $extras = $relatorio['horas_extras']['resumo'] ?? [];
        $salarioBase = $salario['salario_base'] ?? $funcionario['salario_base'] ?? 0;
        ?>
        <section class="perfil-grid">
            <div class="perfil-card perfil-largo"><span>Funcionário</span><strong><?= eRH(funcionarioRH($funcionario)) ?></strong></div>
            <div class="perfil-card"><span>Estado</span><strong><?= eRH($funcionario['estado'] ?? '-') ?></strong></div>
            <div class="perfil-card"><span>Vínculo</span><strong><?= eRH(textoCodigoRH($funcionario['tipo_vinculo'] ?? '')) ?></strong></div>
            <div class="perfil-card"><span>Filial</span><strong><?= eRH($funcionario['filial_nome'] ?? '-') ?></strong></div>
            <div class="perfil-card"><span>Departamento</span><strong><?= eRH($funcionario['departamento_nome'] ?? '-') ?></strong></div>
            <div class="perfil-card"><span>Cargo</span><strong><?= eRH($funcionario['cargo_nome'] ?? '-') ?></strong></div>
            <div class="perfil-card"><span>Admissão</span><strong><?= eRH(dataRH($funcionario['data_admissao'] ?? '')) ?></strong></div>
            <div class="perfil-card"><span>Contrato</span><strong><?= eRH($contrato['numero_contrato'] ?? '-') ?></strong></div>
            <div class="perfil-card"><span>Estado do contrato</span><strong><?= eRH($contrato['estado'] ?? '-') ?></strong></div>
            <div class="perfil-card"><span>Início</span><strong><?= eRH(dataRH($contrato['data_inicio'] ?? '')) ?></strong></div>
            <div class="perfil-card"><span>Fim</span><strong><?= !empty($contrato['data_fim']) ? eRH(dataRH($contrato['data_fim'])) : 'Sem termo' ?></strong></div>
            <div class="perfil-card"><span>Salário base</span><strong><?= eRH(moedaRH($salarioBase)) ?></strong></div>
            <div class="perfil-card"><span>Periodicidade</span><strong><?= eRH(textoCodigoRH($salario['periodicidade'] ?? $contrato['periodicidade_salario'] ?? '')) ?></strong></div>
            <div class="perfil-card"><span>Presenças</span><strong><?= eRH(numeroRH($assiduidade['presencas'] ?? 0)) ?></strong></div>
            <div class="perfil-card"><span>Faltas</span><strong><?= eRH(numeroRH($faltas['total_faltas'] ?? 0)) ?></strong></div>
            <div class="perfil-card"><span>Horas extras aprovadas</span><strong><?= eRH(minutosRH($extras['minutos_aprovados'] ?? 0)) ?></strong></div>
        </section>
    <?php elseif ($config !== null): ?>
        <section class="resumo">
            <?php foreach ($config['resumo'] as [$rotulo, $campo, $formato]): ?>
                <div class="resumo-item"><span><?= eRH($rotulo) ?></span><strong><?= eRH(valorLinhaRH($resumo, $campo, $formato)) ?></strong></div>
            <?php endforeach; ?>
        </section>
        <table>
            <thead>
                <tr><?php foreach ($config['colunas'] as [$rotulo]): ?><th><?= eRH($rotulo) ?></th><?php endforeach; ?></tr>
            </thead>
            <tbody>
                <?php if ($registos === []): ?>
                    <tr>
                        <td class="vazio" colspan="<?= count($config['colunas']) ?>">Nenhum registo encontrado para os filtros selecionados.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($registos as $linha): ?><tr>
                            <?php foreach ($config['colunas'] as [, $campo, $formato]): ?><td><?= eRH(valorLinhaRH($linha, $campo, $formato)) ?></td><?php endforeach; ?>
                        </tr><?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <footer class="rodape">Documento gerado pelo ERP Plus. Total de registos: <?= count($registos) ?>.</footer>
</body>

</html>