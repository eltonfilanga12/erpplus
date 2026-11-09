<?php

declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/../core/Database.php';

$empresaId = (int) (
    $_SESSION['empresa_id']
    ?? $_SESSION['usuario']['empresa_id']
    ?? 0
);

$usuarioId = (int) (
    $_SESSION['usuario_id']
    ?? $_SESSION['usuario']['id']
    ?? 0
);

$reciboId = filter_input(
    INPUT_GET,
    'id',
    FILTER_VALIDATE_INT
);

if ($empresaId <= 0 || $usuarioId <= 0) {
    http_response_code(401);
    exit('Sessão inválida ou expirada.');
}

if (!$reciboId || $reciboId <= 0) {
    http_response_code(400);
    exit('Recibo salarial inválido.');
}

$db = Database::conn();

$sql = "
    SELECT
        rs.*,
        st.nome AS estado,

        fs.codigo AS folha_codigo,
        fs.descricao AS folha_descricao,

        ps.codigo AS periodo_codigo,
        ps.descricao AS periodo_descricao,
        ps.ano AS periodo_ano,
        ps.mes AS periodo_mes,
        ps.data_inicio,
        ps.data_fim,

        emp.nome AS empresa_nome,
        emp.nuit AS empresa_nuit,
        emp.email AS empresa_email,
        emp.telefone AS empresa_telefone,

        COALESCE(
            NULLIF(f.email_profissional, ''),
            NULLIF(f.email_pessoal, '')
        ) AS funcionario_email

    FROM recibos_salarios rs

    INNER JOIN status st
        ON st.id = rs.status_id
       AND st.tipo = 'recibo_salarial'

    INNER JOIN folhas_salariais fs
        ON fs.id = rs.folha_salarial_id
       AND fs.empresa_id = rs.empresa_id

    INNER JOIN periodos_salariais ps
        ON ps.id = rs.periodo_id
       AND ps.empresa_id = rs.empresa_id

    INNER JOIN empresas emp
        ON emp.id = rs.empresa_id

    INNER JOIN funcionarios f
        ON f.id = rs.funcionario_id
       AND f.empresa_id = rs.empresa_id

    WHERE rs.id = :id
      AND rs.empresa_id = :empresa_id

    LIMIT 1
";

$stmt = $db->prepare($sql);
$stmt->execute([
    ':id' => $reciboId,
    ':empresa_id' => $empresaId
]);

$recibo = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$recibo) {
    http_response_code(404);
    exit('Recibo salarial não encontrado.');
}

function e(mixed $valor): string
{
    return htmlspecialchars(
        (string) $valor,
        ENT_QUOTES,
        'UTF-8'
    );
}

function moeda(mixed $valor, string $codigo): string
{
    return number_format(
        (float) $valor,
        2,
        ',',
        '.'
    ) . ' ' . e($codigo);
}

function dataPt(?string $data): string
{
    if (!$data) {
        return '-';
    }

    $timestamp = strtotime($data);

    return $timestamp
        ? date('d/m/Y', $timestamp)
        : '-';
}

function dataHoraPt(?string $data): string
{
    if (!$data) {
        return '-';
    }

    $timestamp = strtotime($data);

    return $timestamp
        ? date('d/m/Y H:i', $timestamp)
        : '-';
}

$moeda = (string) ($recibo['moeda'] ?: 'MZN');
$periodo = $recibo['periodo_descricao']
    ?: $recibo['periodo_codigo']
    ?: sprintf(
        '%02d/%04d',
        (int) $recibo['periodo_mes'],
        (int) $recibo['periodo_ano']
    );
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recibo <?= e($recibo['codigo']) ?></title>

    <style>
        * { box-sizing: border-box; }

        body {
            margin: 0;
            background: #eef1f5;
            color: #1f2937;
            font-family: Arial, Helvetica, sans-serif;
            font-size: 13px;
        }

        .toolbar {
            display: flex;
            justify-content: center;
            gap: 10px;
            padding: 16px;
        }

        .toolbar button {
            border: 0;
            border-radius: 5px;
            padding: 10px 18px;
            cursor: pointer;
            font-weight: 600;
        }

        .btn-print { background: #2563eb; color: #fff; }
        .btn-close { background: #6b7280; color: #fff; }

        .receipt {
            width: 210mm;
            min-height: 297mm;
            margin: 0 auto 25px;
            padding: 16mm;
            background: #fff;
            box-shadow: 0 2px 12px rgba(0, 0, 0, .12);
        }

        .header {
            display: flex;
            justify-content: space-between;
            gap: 24px;
            padding-bottom: 15px;
            border-bottom: 2px solid #1f2937;
        }

        .company h1,
        .title h2 {
            margin: 0 0 6px;
        }

        .title { text-align: right; }
        .muted { color: #6b7280; }

        .grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 10px 24px;
            margin: 18px 0;
        }

        .field {
            padding-bottom: 6px;
            border-bottom: 1px solid #e5e7eb;
        }

        .field strong {
            display: block;
            margin-bottom: 3px;
            font-size: 11px;
            text-transform: uppercase;
            color: #6b7280;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 18px;
        }

        th,
        td {
            border: 1px solid #d1d5db;
            padding: 9px;
        }

        th {
            background: #f3f4f6;
            text-align: left;
        }

        .right { text-align: right; }
        .total-row { font-weight: 700; }
        .net-row { font-size: 16px; background: #ecfdf5; }

        .notes {
            margin-top: 18px;
            padding: 12px;
            background: #f9fafb;
            border: 1px solid #e5e7eb;
        }

        .signatures {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 70px;
            margin-top: 65px;
        }

        .signature {
            padding-top: 8px;
            border-top: 1px solid #374151;
            text-align: center;
        }

        .footer {
            margin-top: 45px;
            padding-top: 10px;
            border-top: 1px solid #d1d5db;
            text-align: center;
            color: #6b7280;
            font-size: 11px;
        }

        @page {
            size: A4;
            margin: 8mm;
        }

        @media print {
            body { background: #fff; }
            .toolbar { display: none !important; }
            .receipt {
                width: auto;
                min-height: auto;
                margin: 0;
                padding: 8mm;
                box-shadow: none;
            }
        }
    </style>
</head>
<body>

    <main class="receipt">
        <header class="header">
            <div class="company">
                <h1><?= e($recibo['empresa_nome']) ?></h1>
                <div>NUIT: <?= e($recibo['empresa_nuit'] ?: '-') ?></div>
                <div><?= e($recibo['empresa_telefone'] ?: '-') ?></div>
                <div><?= e($recibo['empresa_email'] ?: '-') ?></div>
            </div>

            <div class="title">
                <h2>RECIBO DE SALÁRIO</h2>
                <div><strong><?= e($recibo['codigo']) ?></strong></div>
                <div class="muted">
                    Emitido em <?= e(dataHoraPt($recibo['emitido_em'])) ?>
                </div>
                <div>Estado: <?= e($recibo['estado']) ?></div>
            </div>
        </header>

        <section class="grid">
            <div class="field">
                <strong>Funcionário</strong>
                <?= e($recibo['funcionario_nome']) ?>
            </div>

            <div class="field">
                <strong>Número</strong>
                <?= e($recibo['numero_funcionario'] ?: '-') ?>
            </div>

            <div class="field">
                <strong>Cargo</strong>
                <?= e($recibo['cargo_nome'] ?: '-') ?>
            </div>

            <div class="field">
                <strong>Departamento</strong>
                <?= e($recibo['departamento_nome'] ?: '-') ?>
            </div>

            <div class="field">
                <strong>Filial</strong>
                <?= e($recibo['filial_nome'] ?: '-') ?>
            </div>

            <div class="field">
                <strong>Período salarial</strong>
                <?= e($periodo) ?>
            </div>

            <div class="field">
                <strong>Vigência</strong>
                <?= e(dataPt($recibo['data_inicio'])) ?>
                a
                <?= e(dataPt($recibo['data_fim'])) ?>
            </div>

            <div class="field">
                <strong>Folha salarial</strong>
                <?= e($recibo['folha_codigo']) ?>
                <?= $recibo['folha_descricao']
                    ? ' - ' . e($recibo['folha_descricao'])
                    : '' ?>
            </div>
        </section>

        <table>
            <thead>
                <tr>
                    <th>Proventos</th>
                    <th class="right">Valor</th>
                    <th>Descontos</th>
                    <th class="right">Valor</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Salário base</td>
                    <td class="right"><?= moeda($recibo['salario_base'], $moeda) ?></td>
                    <td>Faltas</td>
                    <td class="right"><?= moeda($recibo['total_faltas'], $moeda) ?></td>
                </tr>
                <tr>
                    <td>Subsídios</td>
                    <td class="right"><?= moeda($recibo['total_subsidios'], $moeda) ?></td>
                    <td>Atrasos</td>
                    <td class="right"><?= moeda($recibo['total_atrasos'], $moeda) ?></td>
                </tr>
                <tr>
                    <td>Horas extras</td>
                    <td class="right"><?= moeda($recibo['total_horas_extras'], $moeda) ?></td>
                    <td>INSS</td>
                    <td class="right"><?= moeda($recibo['total_inss'], $moeda) ?></td>
                </tr>
                <tr>
                    <td>Outros proventos</td>
                    <td class="right"><?= moeda($recibo['total_outros_proventos'], $moeda) ?></td>
                    <td>IRPS</td>
                    <td class="right"><?= moeda($recibo['total_irps'], $moeda) ?></td>
                </tr>
                <tr>
                    <td></td>
                    <td></td>
                    <td>Outros descontos</td>
                    <td class="right"><?= moeda($recibo['total_outros_descontos'], $moeda) ?></td>
                </tr>
                <tr class="total-row">
                    <td>Total de proventos</td>
                    <td class="right"><?= moeda($recibo['total_proventos'], $moeda) ?></td>
                    <td>Total de descontos</td>
                    <td class="right"><?= moeda($recibo['total_descontos'], $moeda) ?></td>
                </tr>
                <tr>
                    <td colspan="3"><strong>Salário bruto</strong></td>
                    <td class="right"><strong><?= moeda($recibo['salario_bruto'], $moeda) ?></strong></td>
                </tr>
                <tr class="net-row">
                    <td colspan="3"><strong>Salário líquido</strong></td>
                    <td class="right"><strong><?= moeda($recibo['salario_liquido'], $moeda) ?></strong></td>
                </tr>
                <tr>
                    <td colspan="3">Valor pago</td>
                    <td class="right"><?= moeda($recibo['valor_pago'], $moeda) ?></td>
                </tr>
                <tr>
                    <td colspan="3">Saldo pendente</td>
                    <td class="right"><?= moeda($recibo['saldo_pendente'], $moeda) ?></td>
                </tr>
            </tbody>
        </table>

        <?php if (!empty($recibo['observacoes'])): ?>
            <div class="notes">
                <strong>Observações:</strong><br>
                <?= nl2br(e($recibo['observacoes'])) ?>
            </div>
        <?php endif; ?>

        <section class="signatures">
            <div class="signature">Responsável da empresa</div>
            <div class="signature">Funcionário</div>
        </section>

        <footer class="footer">
            Documento gerado pelo ERP Plus em
            <?= e(date('d/m/Y H:i')) ?>.
        </footer>
    </main>
    <script>
    window.addEventListener(
        'load',
        function () {
            window.print();
        }
    );
</script>
</body>
</html>