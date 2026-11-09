
<?php

require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Auth.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (!Auth::check()) {
    http_response_code(401);
    exit('Sessão expirada. Inicie sessão novamente.');
}

/**
 * Escapar texto para HTML.
 */
function escRecibo(mixed $valor): string
{
    return htmlspecialchars(
        (string) ($valor ?? ''),
        ENT_QUOTES | ENT_SUBSTITUTE,
        'UTF-8'
    );
}

/**
 * Formatar moeda.
 */
function moedaRecibo(mixed $valor): string
{
    return number_format(
        (float) ($valor ?? 0),
        2,
        ',',
        '.'
    ) . ' MT';
}

/**
 * Converter grupos de 0 a 999 em palavras.
 */
function grupoPorExtenso(int $numero): string
{
    $unidades = [
        '',
        'um',
        'dois',
        'três',
        'quatro',
        'cinco',
        'seis',
        'sete',
        'oito',
        'nove',
        'dez',
        'onze',
        'doze',
        'treze',
        'catorze',
        'quinze',
        'dezasseis',
        'dezassete',
        'dezoito',
        'dezanove'
    ];

    $dezenas = [
        '',
        '',
        'vinte',
        'trinta',
        'quarenta',
        'cinquenta',
        'sessenta',
        'setenta',
        'oitenta',
        'noventa'
    ];

    $centenas = [
        '',
        'cento',
        'duzentos',
        'trezentos',
        'quatrocentos',
        'quinhentos',
        'seiscentos',
        'setecentos',
        'oitocentos',
        'novecentos'
    ];

    if ($numero === 0) {
        return '';
    }

    if ($numero === 100) {
        return 'cem';
    }

    $partes = [];

    $centena = intdiv($numero, 100);
    $resto = $numero % 100;

    if ($centena > 0) {
        $partes[] = $centenas[$centena];
    }

    if ($resto > 0 && $resto < 20) {
        $partes[] = $unidades[$resto];
    } elseif ($resto >= 20) {
        $dezena = intdiv($resto, 10);
        $unidade = $resto % 10;

        $textoDezena = $dezenas[$dezena];

        if ($unidade > 0) {
            $textoDezena .= ' e ' . $unidades[$unidade];
        }

        $partes[] = $textoDezena;
    }

    return implode(' e ', $partes);
}

/**
 * Converter um número inteiro em português.
 * Suporta valores até 999 999 999 999.
 */
function numeroPorExtenso(int $numero): string
{
    if ($numero === 0) {
        return 'zero';
    }

    if ($numero < 0 || $numero > 999999999999) {
        return (string) $numero;
    }

    $escalas = [
        1000000000 => ['mil milhões', 'mil milhões'],
        1000000    => ['um milhão', 'milhões'],
        1000       => ['mil', 'mil'],
        1          => ['', '']
    ];

    $partes = [];
    $restante = $numero;

    foreach ($escalas as $escala => $nomes) {
        if ($restante < $escala) {
            continue;
        }

        $quantidade = intdiv($restante, $escala);
        $restante %= $escala;

        if ($escala === 1) {
            $texto = grupoPorExtenso($quantidade);
        } elseif ($escala === 1000) {
            $texto = $quantidade === 1
                ? 'mil'
                : grupoPorExtenso($quantidade) . ' mil';
        } elseif ($escala === 1000000) {
            $texto = $quantidade === 1
                ? 'um milhão'
                : grupoPorExtenso($quantidade) . ' milhões';
        } else {
            $texto = $quantidade === 1
                ? 'mil milhões'
                : grupoPorExtenso($quantidade) . ' mil milhões';
        }

        $partes[] = $texto;
    }

    // Melhorar a ligação da última parte quando aplicável.
    if (count($partes) > 1) {
        $ultimo = array_pop($partes);
        $partes[] = $ultimo;
    }

    return implode(' ', $partes);
}

/**
 * Valor monetário por extenso em meticais e centavos.
 */
function valorPorExtensoRecibo(mixed $valor): string
{
    $valorCentavos = (int) round(
        abs((float) $valor) * 100
    );

    $meticais = intdiv($valorCentavos, 100);
    $centavos = $valorCentavos % 100;

    $texto = numeroPorExtenso($meticais);

    $texto .= $meticais === 1
        ? ' metical'
        : ' meticais';

    if ($centavos > 0) {
        $texto .= ' e ' . numeroPorExtenso($centavos);

        $texto .= $centavos === 1
            ? ' centavo'
            : ' centavos';
    }

    if ((float) $valor < 0) {
        $texto = 'menos ' . $texto;
    }

    return ucfirst($texto);
}

/**
 * Carregar logótipo local da empresa.
 */
function logoRecibo(?string $nomeLogo): string
{
    $nomeLogo = trim((string) $nomeLogo);

    if ($nomeLogo === '') {
        return '';
    }

    $nomeFicheiro = basename(
        str_replace('\\', '/', $nomeLogo)
    );

    $raiz = realpath(__DIR__ . '/../..');

    if ($raiz === false || $nomeFicheiro === '') {
        return '';
    }

    $pastas = [
        '/public/uploads/empresas/',
        '/public/uploads/empresa/',
        '/uploads/empresas/',
        '/uploads/empresa/',
        '/public/assets/images/empresas/',
        '/public/admin/dist/img/',
        '/admin/dist/img/',
    ];

    foreach ($pastas as $pasta) {
        $caminho = $raiz . $pasta . $nomeFicheiro;

        if (!is_file($caminho) || !is_readable($caminho)) {
            continue;
        }

        $mime = mime_content_type($caminho);

        if (!in_array($mime, [
            'image/jpeg',
            'image/png',
            'image/gif',
            'image/webp',
        ], true)) {
            continue;
        }

        $conteudo = file_get_contents($caminho);

        if ($conteudo !== false) {
            return 'data:' . $mime . ';base64,'
                . base64_encode($conteudo);
        }
    }

    return '';
}

$httpStatus = 500;

try {
    $id = filter_input(
        INPUT_GET,
        'id',
        FILTER_VALIDATE_INT
    );

    if (!$id || $id <= 0) {
        throw new RuntimeException(
            'Identificador do recebimento inválido.'
        );
    }

    $empresaId = (int) Auth::empresaId();

    if ($empresaId <= 0) {
        throw new RuntimeException(
            'Empresa da sessão inválida.'
        );
    }

    $autoload = __DIR__ . '/../../vendor/autoload.php';

    if (!is_file($autoload)) {
        throw new RuntimeException(
            'Dompdf não encontrado.'
        );
    }

    require_once $autoload;

    if (!class_exists(\Dompdf\Dompdf::class)) {
        throw new RuntimeException(
            'A classe Dompdf não está disponível.'
        );
    }

    $pdo = Database::conn();

    /*
     * CONSULTA APENAS.
     * Não regista pagamentos nem altera movimentos financeiros.
     */
    $sql = "
        SELECT
            r.id AS recebimento_id,
            r.empresa_id,
            r.valor AS valor_recebido,
            r.data_recebimento,
            r.referencia,
            r.observacoes AS observacoes_recebimento,
            r.conta_receber_id,

            cr.numero_documento,
            cr.data_emissao,
            cr.valor AS valor_documento,

            cliente.nome AS cliente_nome,
            cliente.nuit AS cliente_nuit,
            cliente.telefone AS cliente_telefone,

            fp.nome AS forma_pagamento,

            e.nome,
            e.nome_fantasia,
            e.nuit AS empresa_nuit,
            e.email AS empresa_email,
            e.telefone AS empresa_telefone,
            e.logradouro,
            e.cidade AS empresa_cidade,
            e.provincia,
            e.pais,
            e.logo,
            e.moeda

        FROM recebimentos r

        INNER JOIN contas_receber cr
            ON cr.id = r.conta_receber_id
            AND cr.empresa_id = r.empresa_id

        INNER JOIN clientes cliente
            ON cliente.id = cr.cliente_id
            AND cliente.empresa_id = cr.empresa_id

        INNER JOIN empresas e
            ON e.id = r.empresa_id

        LEFT JOIN formas_pagamento fp
            ON fp.id = r.forma_pagamento_id

        WHERE r.id = :recebimento_id
          AND r.empresa_id = :empresa_id

        LIMIT 1
    ";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        ':recebimento_id' => $id,
        ':empresa_id' => $empresaId,
    ]);

    $dados = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$dados) {
        $httpStatus = 404;

        throw new RuntimeException(
            'Recibo não encontrado.'
        );
    }

    $logo = logoRecibo($dados['logo'] ?? null);

    $nomeEmpresa = trim(
        (string) (
            $dados['nome']
            ?: $dados['nome']
            ?: 'Empresa'
        )
    );

    // Corrigido: o alias SQL é empresa_cidade.
    $enderecoEmpresa = implode(', ', array_filter([
        $dados['logradouro'] ?? '',
        $dados['empresa_cidade'] ?? '',
        $dados['provincia'] ?? '',
        $dados['pais'] ?? '',
    ]));

    $timestamp = !empty($dados['data_recebimento'])
        ? strtotime($dados['data_recebimento'])
        : false;

    $data = $timestamp !== false
        ? date('d/m/Y H:i', $timestamp)
        : '';

    $numeroRecibo = 'REC-' . str_pad(
        (string) $dados['recebimento_id'],
        6,
        '0',
        STR_PAD_LEFT
    );

    $valor = (float) $dados['valor_recebido'];
    $valorFormatado = moedaRecibo($valor);
    $valorExtenso = valorPorExtensoRecibo($valor);

    $documento = trim(
        (string) ($dados['numero_documento'] ?? '')
    );

    if ($documento === '') {
        $documento = 'Conta #' . $dados['conta_receber_id'];
    }

    $formaPagamento = trim(
        (string) ($dados['forma_pagamento'] ?? '')
    );

    if ($formaPagamento === '') {
        $formaPagamento = 'Não especificada';
    }

    $referencia = trim(
        (string) ($dados['referencia'] ?? '')
    );

    if ($referencia === '') {
        $referencia = 'Não indicada';
    }

    $htmlLogo = $logo !== ''
        ? '<img class="logo" src="' . $logo . '" alt="Logótipo">'
        : '';

    $htmlNuitEmpresa = !empty($dados['empresa_nuit'])
        ? '<div>NUIT: ' . escRecibo($dados['empresa_nuit']) . '</div>'
        : '';

    $htmlTelefoneEmpresa = !empty($dados['empresa_telefone'])
        ? '<div>Telefone: ' . escRecibo($dados['empresa_telefone']) . '</div>'
        : '';

    $htmlEmailEmpresa = !empty($dados['empresa_email'])
        ? '<div>' . escRecibo($dados['empresa_email']) . '</div>'
        : '';

    $htmlNuitCliente = !empty($dados['cliente_nuit'])
        ? '<span>NUIT: ' . escRecibo($dados['cliente_nuit']) . '</span>'
        : '';

    $htmlTelefoneCliente = !empty($dados['cliente_telefone'])
        ? '<span>Telefone: ' . escRecibo($dados['cliente_telefone']) . '</span>'
        : '';

    $htmlObservacoes = !empty($dados['observacoes_recebimento'])
        ? '<div class="observacoes"><strong>Observações:</strong> '
        . nl2br(escRecibo($dados['observacoes_recebimento']))
        . '</div>'
        : '';

    $html = '
    <!DOCTYPE html>
    <html lang="pt">
    <head>
        <meta charset="UTF-8">

        <style>
            @page {
                size: A5 landscape;
                margin: 8mm 10mm;
            }

            * {
                box-sizing: border-box;
            }

            body {
                font-family: "DejaVu Sans", sans-serif;
                font-size: 9px;
                color: #263746;
                line-height: 1.4;
                margin: 0;
            }

            table {
                border-collapse: collapse;
            }

            .cabecalho {
                width: 100%;
                border-bottom: 2px solid #17324d;
                padding-bottom: 7px;
                margin-bottom: 10px;
            }

            .cabecalho td {
                vertical-align: top;
            }

            .logo {
                width: 180px;
                height: auto;
                margin-bottom: 3px;
            }

            .empresa {
                font-size: 11px;
                font-weight: bold;
                color: #17324d;
            }

            .dados-empresa {
                font-size: 8px;
                color: #52616d;
            }

            .titulo {
                text-align: right;
                font-size: 20px;
                font-weight: bold;
                color: #17324d;
                letter-spacing: 1px;
            }

            .numero {
                text-align: right;
                font-size: 10px;
                margin-top: 3px;
            }

            .subtitulo {
                color: #627181;
                font-size: 8px;
            }

            .cliente {
                border: 1px solid #d9e1e8;
                padding: 8px 10px;
                margin-bottom: 8px;
            }

            .rotulo {
                color: #627181;
                font-size: 8px;
                text-transform: uppercase;
                margin-bottom: 2px;
            }

            .nome-cliente {
                font-size: 11px;
                font-weight: bold;
                color: #17324d;
                margin-bottom: 3px;
            }

            .dados-cliente {
                font-size: 8px;
                color: #52616d;
            }

            .declaracao {
                margin: 10px 0;
                padding: 10px 12px;
                background: #f3f6f9;
                border-left: 3px solid #17324d;
                font-size: 10px;
                line-height: 1.8;
                text-align: justify;
            }

            .montante {
                font-weight: bold;
                color: #17643a;
                font-size: 11px;
            }

            .extenso {
                font-weight: bold;
            }

            .detalhes {
                width: 100%;
                border: 1px solid #d9e1e8;
                margin-top: 7px;
            }

            .detalhes td {
                width: 50%;
                padding: 5px 8px;
                border-bottom: 1px solid #e5eaf0;
                vertical-align: top;
            }

            .detalhes tr:last-child td {
                border-bottom: none;
            }

            .detalhe-label {
                display: block;
                color: #627181;
                font-size: 7px;
                text-transform: uppercase;
                margin-bottom: 2px;
            }

            .detalhe-valor {
                font-weight: bold;
                font-size: 9px;
            }

            .observacoes {
                margin-top: 6px;
                font-size: 8px;
            }

            .confirmacao {
                margin-top: 8px;
                font-size: 8px;
            }

            .assinaturas {
                width: 220px;
                margin-top: 20px;
                margin-left: auto;
                margin-right: auto;
            }

            .assinaturas td {
                width: 220px;
                padding: 0;
                text-align: center;
                vertical-align: bottom;
            }

            .linha-assinatura {
                width: 180px;
                margin: 0 auto;
                border-top: 1px solid #52616d;
                padding-top: 4px;
                font-size: 8px;
            }

            .rodape {
                border-top: 1px solid #d9e1e8;
                margin-top: 10px;
                padding-top: 4px;
                text-align: center;
                color: #627181;
                font-size: 7px;
            }
        </style>
    </head>

    <body>

        <table class="cabecalho">
            <tr>
                <td width="65%">
                    ' . $htmlLogo . '

                    <div class="empresa">'
        . escRecibo($nomeEmpresa) .
        '</div>

                    <div class="dados-empresa">'
        . escRecibo($enderecoEmpresa) . '
                        ' . $htmlNuitEmpresa . '
                        ' . $htmlTelefoneEmpresa . '
                        ' . $htmlEmailEmpresa . '
                    </div>
                </td>

                <td width="35%">
                    <div class="titulo">RECIBO</div>

                    <div class="numero">'
        . escRecibo($numeroRecibo) .
        '</div>

                    <div class="numero subtitulo">
                        Data: ' . escRecibo($data) . '
                    </div>
                </td>
            </tr>
        </table>

        <div class="cliente">
            <div class="rotulo">Recebemos de</div>

            <div class="nome-cliente">'
        . escRecibo($dados['cliente_nome']) .
        '</div>

            <div class="dados-cliente">
                ' . $htmlNuitCliente . '
                ' . $htmlTelefoneCliente . '
            </div>
        </div>

        <div class="declaracao">
            Recebemos de
            <strong>' . escRecibo($dados['cliente_nome']) . '</strong>
            a quantia de
            <strong>' . escRecibo($valorFormatado) . '</strong>
            (<strong>' . escRecibo($valorExtenso) . '</strong>),
            referente ao documento
            <strong>' . escRecibo($documento) . '</strong>,
            paga por meio de
            <strong>' . escRecibo($formaPagamento) . '</strong>,
            na data de
            <strong>' . escRecibo($data) . '</strong>.
        </div>

        <table class="detalhes">
            <tr>
                <td>
                    <span class="detalhe-label">Forma de pagamento</span>
                    <span class="detalhe-valor">'
        . escRecibo($formaPagamento) .
        '</span>
                </td>

                <td>
                    <span class="detalhe-label">Referência do pagamento</span>
                    <span class="detalhe-valor">'
        . escRecibo($referencia) .
        '</span>
                </td>
            </tr>
        </table>

        ' . $htmlObservacoes . '

        <div class="confirmacao">
            Pelo presente recibo, declaramos ter recebido o valor acima
            indicado, correspondente ao pagamento identificado neste documento.
        </div>
<br>
        <table class="assinaturas">
            <tr>
                <td>
                    <div class="linha-assinatura">
                        Assinatura do responsável
                    </div>
                </td>

            </tr>
        </table>

        <div class="rodape">
            Documento emitido pelo ERP Plus |
            Recibo ' . escRecibo($numeroRecibo) . '
        </div>

    </body>
    </html>';

    $opcoes = new \Dompdf\Options();
    $opcoes->set('isRemoteEnabled', false);
    $opcoes->set('isHtml5ParserEnabled', true);
    $opcoes->set('defaultFont', 'DejaVu Sans');

    $dompdf = new \Dompdf\Dompdf($opcoes);

    $dompdf->loadHtml($html, 'UTF-8');

    // Papel A5 em orientação horizontal.
    $dompdf->setPaper('A5', 'landscape');

    $dompdf->render();

    $dompdf->stream(
        $numeroRecibo . '.pdf',
        ['Attachment' => false]
    );

    exit;
} catch (Throwable $e) {
    error_log(
        '[ERP Plus - Recibo PDF] '
            . $e->getMessage()
            . ' em '
            . $e->getFile()
            . ':'
            . $e->getLine()
    );

    if (!headers_sent()) {
        http_response_code($httpStatus);
        header('Content-Type: text/plain; charset=UTF-8');
    }

    exit($httpStatus === 404
        ? 'Recibo não encontrado.'
        : 'Não foi possível gerar o recibo. Verifique os registos do sistema.');
}
