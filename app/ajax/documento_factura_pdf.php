
<?php


require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../models/Venda.php';
require_once __DIR__ . '/../models/DocumentoVenda.php';
require_once __DIR__ . '/../controllers/DocumentoVendaController.php';

Auth::iniciarSessao();

if (!Auth::check()) {
    http_response_code(401);
    exit('Sessão expirada. Inicie sessão novamente.');
}

function escPdf(mixed $valor): string
{
    return htmlspecialchars(
        (string)($valor ?? ''),
        ENT_QUOTES | ENT_SUBSTITUTE,
        'UTF-8'
    );
}

function dataPdf(mixed $valor, bool $hora = false): string
{
    if (!$valor) {
        return '-';
    }

    $data = substr((string)$valor, 0, 10);
    $partes = explode('-', $data);

    if (count($partes) !== 3) {
        return (string)$valor;
    }

    $resultado = $partes[2] . '/' . $partes[1] . '/' . $partes[0];

    if ($hora && strlen((string)$valor) >= 16) {
        $resultado .= ' ' . substr((string)$valor, 11, 5);
    }

    return $resultado;
}

function moedaPdf(mixed $valor): string
{
    return number_format(
        (float)($valor ?? 0),
        2,
        ',',
        '.'
    ) . ' MT';
}

/**
 * Localiza o logótipo e converte-o em Data URI.
 * Assim, o Dompdf não depende de carregar imagens por URL.
 */
function logoPdf(?string $nomeLogo): string
{
    $nomeLogo = trim((string)$nomeLogo);

    if ($nomeLogo === '') {
        return '';
    }

    // Evita que o nome do ficheiro permita sair da pasta.
    $nomeFicheiro = basename(str_replace('\\', '/', $nomeLogo));

    if ($nomeFicheiro === '' || $nomeFicheiro === '.' || $nomeFicheiro === '..') {
        return '';
    }

    $raiz = realpath(__DIR__ . '/../..');

    if ($raiz === false) {
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

        // Apenas formatos de imagem suportados neste documento.
        if (!in_array($mime, [
            'image/jpeg',
            'image/png',
            'image/gif',
            'image/webp',
        ], true)) {
            continue;
        }

        $conteudo = file_get_contents($caminho);

        if ($conteudo === false) {
            continue;
        }

        return 'data:' . $mime . ';base64,' . base64_encode($conteudo);
    }

    return '';
}

try {
    $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

    if (!$id || $id <= 0) {
        throw new RuntimeException('Identificador da factura inválido.');
    }

    $autoload = __DIR__ . '/../../vendor/autoload.php';

    if (!is_file($autoload)) {
        throw new RuntimeException(
            'Dompdf não encontrado. Verifique vendor/autoload.php.'
        );
    }

    require_once $autoload;

    if (!class_exists(\Dompdf\Dompdf::class)) {
        throw new RuntimeException('A classe Dompdf não foi encontrada.');
    }

    $controller = new DocumentoVendaController(Database::conn());
    $dados = $controller->obterFactura((int)$id);

    $empresa = $dados['empresa'];
    $venda = $dados['venda'];
    $itens = $dados['itens'];
    $conta = $dados['conta_receber'] ?? [];

    $tipo = strtoupper(trim((string)($venda['tipo_documento'] ?? '')));

    if ($tipo !== 'FACTURA') {
        throw new RuntimeException('Este documento não é uma factura.');
    }

    $logo = logoPdf($empresa['logo'] ?? null);

    $nomeEmpresa = escPdf(
        $empresa['nome'] ?: ($empresa['nome'] ?? '')
    );

    $endereco = implode(', ', array_filter([
        $empresa['logradouro'] ?? '',
        $empresa['cidade'] ?? '',
        $empresa['provincia'] ?? '',
        $empresa['pais'] ?? '',
    ]));

    $total = (float)($venda['total'] ?? 0);
    $recebido = (float)($conta['valor_recebido'] ?? 0);

    $saldo = isset($conta['saldo'])
        ? (float)$conta['saldo']
        : max(0, $total - $recebido);

    $linhas = '';

    foreach ($itens as $indice => $item) {
        $linhas .= '
            <tr>
                <td class="centro">' . ($indice + 1) . '</td>
                <td>' . escPdf(
            $item['produto_nome'] ?? $item['nome_produto'] ?? 'Produto'
        ) . '</td>
                <td class="direita">' . rtrim(
            rtrim(
                number_format(
                    (float)($item['quantidade'] ?? 0),
                    3,
                    ',',
                    '.'
                ),
                '0'
            ),
            ','
        ) . '</td>
                <td class="direita">' . moedaPdf(
            $item['preco_unitario'] ?? 0
        ) . '</td>
                <td class="direita">' . moedaPdf(
            $item['subtotal'] ?? 0
        ) . '</td>
            </tr>';
    }

    if ($linhas === '') {
        $linhas = '
            <tr>
                <td colspan="5" class="centro">
                    Sem artigos associados a esta factura.
                </td>
            </tr>';
    }

    $taxa = (float)($venda['taxa_imposto'] ?? 0);

    $html = '
    <!DOCTYPE html>
    <html lang="pt">
    <head>
        <meta charset="UTF-8">
        <style>
            @page {
                margin: 25mm 15mm 20mm 15mm;
            }

            body {
                font-family: "DejaVu Sans", sans-serif;
                font-size: 10px;
                color: #263746;
            }

            .cabecalho {
                width: 100%;
                border-bottom: 3px solid #17324d;
                padding-bottom: 12px;
            }

            .cabecalho td {
                vertical-align: top;
            }

            .logo {
                width: 180px;
                height: auto;
                margin-right: 10px;
            }

            .nome {
                font-size: 12px;
                font-weight: bold;
                color: #17324d;
                margin-bottom: 5px;
            }

            .secundario {
                color: #596775;
                line-height: 1.6;
            }

            .titulo {
                text-align: right;
                width: 180px;
            }

            .titulo h1 {
                font-size: 23px;
                color: #17324d;
                margin: 0 0 8px;
            }

            .numero {
                font-size: 11px;
                font-weight: bold;
            }

            .informacoes {
                width: 100%;
                margin-top: 18px;
                margin-bottom: 18px;
            }

            .informacoes td {
                width: 50%;
                vertical-align: top;
                padding: 10px;
                border: 1px solid #dce3e9;
            }

            .rotulo {
                font-size: 9px;
                color: #596775;
                margin-bottom: 7px;
                font-weight: bold;
            }

            .cliente {
                font-size: 10px;
                font-weight: bold;
                margin-bottom: 5px;
            }

            .itens {
                width: 100%;
                border-collapse: collapse;
                table-layout: fixed;
            }

            .itens th {
                background: #17324d;
                color: #ffffff;
                padding: 9px 6px;
                font-size: 9px;
                font-weight: bold;
                vertical-align: middle;
                text-align: left;
            }

            .itens td {
                padding: 9px 6px;
                border-bottom: 1px solid #dce3e9;
                vertical-align: middle;
            }

            /* Coluna N.º */
            .itens th:nth-child(1),
            .itens td:nth-child(1) {
                width: 8%;
                text-align: center;
            }

            /* Coluna Descrição */
            .itens th:nth-child(2),
            .itens td:nth-child(2) {
                width: 44%;
                text-align: left;
            }

            /* Coluna Quantidade */
            .itens th:nth-child(3),
            .itens td:nth-child(3) {
                width: 12%;
                text-align: right;
            }

            /* Coluna Preço Unitário */
            .itens th:nth-child(4),
            .itens td:nth-child(4) {
                width: 18%;
                text-align: right;
            }

            /* Coluna Subtotal */
            .itens th:nth-child(5),
            .itens td:nth-child(5) {
                width: 18%;
                text-align: right;
            }

            /* Garantir alinhamento dos títulos numéricos */
            .itens th:nth-child(3),
            .itens th:nth-child(4),
            .itens th:nth-child(5) {
                text-align: right;
            }

            .centro {
                text-align: center;
            }

            .direita {
                text-align: right;
                white-space: nowrap;
            }

            .resumo {
                width: 270px;
                margin: 18px 0 0 auto;
            }

            .resumo td {
                padding: 6px 0;
            }

            .total {
                border-top: 2px solid #17324d;
                font-size: 10px;
                font-weight: bold;
                color: #17324d;
            }

            .saldo {
                background: #fff0d4;
                color: #754800;
                font-weight: bold;
            }

            .observacoes {
                margin-top: 22px;
                padding: 10px;
                border: 1px solid #dce3e9;
            }

            .assinaturas {
                width: 100%;
                margin-top: 55px;
            }

            .assinaturas td {
                width: 50%;
                text-align: center;
                padding-top: 8px;
                border-top: 1px solid #777;
            }

            .rodape {
                margin-top: 25px;
                padding-top: 10px;
                border-top: 1px solid #dce3e9;
                text-align: center;
                color: #596775;
                font-size: 8px;
            }
        </style>
    </head>
    <body>

        <table class="cabecalho">
            <tr>
                <td>
                    <table>
                        <tr>
                            <td>
                            <img class="logo" src="' . $logo . '">
                                <div class="nome">' . $nomeEmpresa . '</div>
                                <div class="secundario">' .
        escPdf($endereco) . '</div>
                                <div class="secundario">NUIT: ' .
        escPdf($empresa['nuit'] ?? '-') . '</div>
                                <div class="secundario">Tel.: ' .
        escPdf($empresa['telefone'] ?? '-') . '</div>
                                <div class="secundario">' .
        escPdf($empresa['email'] ?? '') . '</div>
                            </td>
                        </tr>
                    </table>
                </td>
                <td class="titulo">
                    <h1>FACTURA</h1>
                    <div class="numero">N.º ' .
        escPdf($venda['numero_fatura'] ?? '') . '</div>
                    <br>
                    <strong>Condição:</strong> A prazo
                </td>
            </tr>
        </table>

        <table class="informacoes">
            <tr>
                <td>
                    <div class="rotulo">FACTURADO A</div>
                    <div class="cliente">' .
        escPdf($venda['cliente_nome'] ?? 'Cliente') . '</div>
                </td>
                <td>
                    <div class="rotulo">INFORMAÇÕES DO DOCUMENTO</div>
                    <div><strong>Emissão:</strong> ' .
        dataPdf($venda['data_venda'] ?? null, true) . '</div>
                    <div><strong>Vencimento:</strong> ' .
        dataPdf($venda['data_vencimento'] ?? null) . '</div>
                    <div><strong>Filial:</strong> ' .
        escPdf($venda['filial_nome'] ?? '-') . '</div>
                </td>
            </tr>
        </table>

        <table class="itens">
            <thead>
                <tr>
                    <th>N.º</th>
                    <th>DESCRIÇÃO</th>
                    <th>QTD.</th>
                    <th>PREÇO UNIT.</th>
                    <th>SUBTOTAL</th>
                </tr>
            </thead>
            <tbody>' . $linhas . '</tbody>
        </table>

        <table class="resumo">
            <tr>
                <td>Subtotal</td>
                <td class="direita">' . moedaPdf($venda['subtotal'] ?? 0) . '</td>
            </tr>
            <tr>
                <td>Desconto</td>
                <td class="direita">' . moedaPdf($venda['desconto'] ?? 0) . '</td>
            </tr>
            <tr>
                <td>IVA' . ($taxa > 0 ? ' (' . $taxa . '%)' : '') . '</td>
                <td class="direita">' . moedaPdf($venda['imposto'] ?? 0) . '</td>
            </tr>
            <tr class="total">
                <td>TOTAL</td>
                <td class="direita">' . moedaPdf($total) . '</td>
            </tr>
            <tr>
                <td>Valor recebido</td>
                <td class="direita">' . moedaPdf($recebido) . '</td>
            </tr>
            <tr class="saldo">
                <td>Saldo por pagar</td>
                <td class="direita">' . moedaPdf($saldo) . '</td>
            </tr>
        </table>

        <div class="observacoes">
            <strong>Observações:</strong><br>' .
        nl2br(escPdf($venda['observacoes'] ?? 'Sem observações.')) . '
        </div>

        <table class="assinaturas">
            <tr>
                <td>Assinatura do emitente</td>
                <td>Assinatura do cliente</td>
            </tr>
        </table>

        <div class="rodape">' .
        escPdf($empresa['slogan'] ?? '') . '<br>
            Documento emitido pelo ERP Plus.
        </div>

    </body>
    </html>';

    $dompdf = new \Dompdf\Dompdf([
        'defaultFont' => 'DejaVu Sans',
        'isRemoteEnabled' => false,
    ]);

    $dompdf->loadHtml($html, 'UTF-8');
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();

    $numero = preg_replace(
        '/[^A-Za-z0-9_-]/',
        '_',
        (string)($venda['numero_fatura'] ?? $id)
    );

    $dompdf->stream(
        'factura_' . $numero . '.pdf',
        ['Attachment' => false]
    );

    exit;
} catch (Throwable $e) {
    error_log('Erro PDF factura: ' . $e->getMessage());

    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');

    exit('Não foi possível gerar a factura PDF. ' .
        'Verifique o log de erros do PHP.');
}
