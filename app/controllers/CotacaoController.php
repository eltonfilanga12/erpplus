<?php

declare(strict_types=1);

require_once __DIR__
    . '/../core/Database.php';

require_once __DIR__
    . '/../core/Auth.php';

require_once __DIR__
    . '/../models/Cotacao.php';

require_once __DIR__
    . '/../models/Cliente.php';

require_once __DIR__
    . '/../models/Produto.php';

require_once __DIR__
    . '/../services/AuditoriaService.php';

require_once __DIR__
    . '/../services/LogService.php';


final class CotacaoController
{
    private PDO $db;

    private Cotacao $model;

    private Cliente $clienteModel;

    private Produto $produtoModel;

    private AuditoriaService $auditoria;

    private LogService $log;


    public function __construct()
    {
        $this->db =
            Database::conn();

        $this->model =
            new Cotacao(
                $this->db
            );

        $this->clienteModel =
            new Cliente(
                $this->db
            );

        $this->produtoModel =
            new Produto(
                $this->db
            );

        $this->auditoria =
            new AuditoriaService(
                $this->db
            );

        $this->log =
            new LogService(
                $this->db
            );
    }


    // =========================================================
    // LISTAR
    // =========================================================

    public function listar(): array
    {
        try {

            return [
                'sucesso' => true,

                'dados' =>
                $this->model->listar(
                    $this->empresaId()
                )
            ];
        } catch (Throwable $e) {

            return $this->erro($e);
        }
    }


    // =========================================================
    // BUSCAR
    // =========================================================

    public function buscar(): array
    {
        try {

            $empresaId =
                $this->empresaId();

            $id =
                $this->inteiroObrigatorio(
                    $_GET['id']
                        ?? $_POST['id']
                        ?? null,
                    'Cotação inválida.'
                );

            $cotacao =
                $this->model->buscar(
                    $id,
                    $empresaId
                );

            if (!$cotacao) {

                return [
                    'sucesso' => false,

                    'mensagem' =>
                    'Cotação não encontrada.'
                ];
            }

            return [
                'sucesso' => true,

                'dados' =>
                $cotacao
            ];
        } catch (Throwable $e) {

            return $this->erro($e);
        }
    }


    // =========================================================
    // IMPRIMIR / GERAR PDF
    // =========================================================

    public function imprimir(): never
    {
        try {

            $empresaId =
                $this->empresaId();

            $id =
                $this->inteiroObrigatorio(
                    $_GET['id']
                        ?? $_POST['id']
                        ?? null,
                    'Cotação inválida.'
                );


            // =================================================
            // BUSCAR DADOS
            // =================================================

            $dados =
                $this->model
                ->dadosParaImpressao(
                    $id,
                    $empresaId
                );


            if (!$dados) {

                $this->respostaPdfErro(
                    'Cotação não encontrada.'
                );
            }


            // =================================================
            // DOMPDF
            // =================================================

            $autoload =
                __DIR__
                . '/../../vendor/autoload.php';


            if (
                !file_exists(
                    $autoload
                )
            ) {

                throw new RuntimeException(
                    'Dompdf não está instalado. '
                        . 'Verifique a pasta vendor/autoload.php.'
                );
            }


            require_once $autoload;


            if (
                !class_exists(
                    '\Dompdf\Dompdf'
                )
            ) {

                throw new RuntimeException(
                    'A biblioteca Dompdf não foi encontrada.'
                );
            }


            $html =
                $this->montarHtmlCotacao(
                    $dados
                );


            $dompdf =
                new \Dompdf\Dompdf([
                    'enable_remote' => true,
                    'isRemoteEnabled' => true,
                    'defaultFont' => 'Times-Roman'
                ]);


            $dompdf->loadHtml(
                $html,
                'UTF-8'
            );


            $dompdf->setPaper(
                'A4',
                'portrait'
            );


            $this->configurarRodapeBancario(
                $dompdf,
                $dados
            );

            // Gerar o PDF
            $dompdf->render();


            $numero =
                preg_replace(
                    '/[^A-Za-z0-9_-]/',
                    '_',
                    (string)(
                        $dados['numero']
                        ?? 'cotacao'
                    )
                );


            $nomeArquivo =
                'cotacao_'
                . $numero
                . '.pdf';


            /*
             * O true no segundo parâmetro força download.
             *
             * Se preferir que o navegador abra o PDF
             * para visualização/impressão, usamos false.
             */

            $dompdf->stream(
                $nomeArquivo,
                [
                    'Attachment' => false
                ]
            );

            exit;
        } catch (Throwable $e) {

            error_log(
                sprintf(
                    '[CotacaoController::imprimir] %s em %s:%d',
                    $e->getMessage(),
                    $e->getFile(),
                    $e->getLine()
                )
            );

            $this->respostaPdfErro(
                $e->getMessage()
            );
        }
    }


    // =========================================================
    // MONTAR HTML DA COTAÇÃO
    // =========================================================

    // =========================================================
    // MONTAR HTML DA COTAÇÃO (LAYOUT SOLICITADO)
    // =========================================================

    // =========================================================
    // MONTAR HTML DA COTAÇÃO (IMAGEM FIXA E RODAPÉ NO FIM DA PÁGINA)
    // =========================================================

    // =========================================================
    // MONTAR HTML DA COTAÇÃO (IMAGEM FIXA E RODAPÉ NO FIM DA PÁGINA)
    // =========================================================

    // =========================================================
    // MONTAR HTML DA COTAÇÃO (LAYOUT SOLICITADO)
    // =========================================================

    private function montarHtmlCotacao(
        array $dados
    ): string {

        // DADOS DA EMPRESA E CLIENTE
        $imagemT = $this->esc($dados['empresa_logo'] ?? 'default.png');
        $endereco_empresa = $this->esc($dados['empresa_logradouro'] ?? '');
        $contacto_empresa = $this->esc($dados['empresa_telefone'] ?? '');
        $nuit_empresa = $this->esc($dados['empresa_nuit'] ?? '');
        $emailT = $this->esc($dados['empresa_email'] ?? '');
        $website = $this->esc($dados['empresa_website'] ?? '');

        $client_id = $this->esc($dados['cliente_nome'] ?? '');
        $nuitClient = $this->esc($dados['cliente_nuit'] ?? '');
        $enderecoClient = $this->esc($dados['cliente_endereco'] ?? '');
        $telefoneClient = $this->esc($dados['cliente_telefone'] ?? '');

        // DADOS DO DOCUMENTO
        $fatura_date = $this->formatarData($dados['data_emissao'] ?? null);
        $fatura_datav = $this->formatarData($dados['validade'] ?? null);
        $moeda = strtoupper((string)($dados['empresa_moeda'] ?? 'MZN'));
        $tipo_fatura = 'cotacao'; // Definição fixa para cotação
        $codigo_fatura = $this->esc($dados['numero'] ?? '');
        $ano_fatura = date('Y');

        // VALORES
        $sub_total = $this->formatarMoeda($dados['subtotal'] ?? 0);
        $discount = $this->formatarMoeda($dados['desconto_valor'] ?? 0);
        $ivaT = $this->formatarMoeda($dados['imposto_valor'] ?? 0);
        $total_amount = $this->formatarMoeda($dados['total'] ?? 0);
        $obs = $this->esc($dados['observacoes'] ?? '');

        // =====================================================
        // DADOS DA EMPRESA
        // =====================================================

        $nomeEmpresa = trim(
            (string)(
                $dados['empresa_nome_fantasia']
                ?? ''
            )
        );

        if ($nomeEmpresa === '') {
            $nomeEmpresa = trim(
                (string)(
                    $dados['empresa_nome']
                    ?? ''
                )
            );
        }

        $nomeEmpresa = $this->esc($nomeEmpresa);

        $endereco_empresa = $this->montarEnderecoEmpresa(
            (string)($dados['empresa_logradouro'] ?? ''),
            (string)($dados['empresa_cidade'] ?? ''),
            (string)($dados['empresa_provincia'] ?? ''),
            (string)($dados['empresa_codigo_postal'] ?? ''),
            (string)($dados['empresa_pais'] ?? '')
        );

        $contacto_empresa = $this->esc(
            $dados['empresa_telefone'] ?? ''
        );

        $nuit_empresa = $this->esc(
            $dados['empresa_nuit'] ?? ''
        );

        $emailT = $this->esc(
            $dados['empresa_email'] ?? ''
        );

        $website = $this->esc(
            $dados['empresa_website'] ?? ''
        );

        // TIPO DE FATURA BADGE
        if ($tipo_fatura == 'cotacao') {
            $tipoFa = "<span class='badge badge-success' style='font-size: 11px;'>COTAÇÃO</span>";
        } elseif ($tipo_fatura == 'fatura') {
            $tipoFa = "<span class='badge badge-danger' style='font-size: 11px;'>FATURA</span>";
        } else {
            $tipoFa = "";
        }

        // MONTAR HTML
        $html = "<!DOCTYPE html>";
        $html .= "<html lang='pt-br'>";
        $html .= "<head>";
        $html .= "<meta charset='UTF-8'>";
        $html .= "<title>Cotação</title>";
        $html .= "
            <style>

                @page {
                    /* Margens padrão: Topo 15mm, Direita 15mm, Fundo 35mm (reserva para o rodapé), Esquerda 15mm */
                    margin: 15mm 15mm 35mm 15mm;
                }

                body {
                    font-family: 'Times New Roman', Times, serif;
                    font-size: 12px;
                    color: #000;
                    line-height: 1.3;
                    margin: 0;
                    padding: 0;
                }

                /* CABEÇALHO */
                .header-cotacao {
                    page-break-inside: avoid;
                }

                .logo-container {
                    width: 180px;
                    height: 65px;
                    margin-bottom: 6px;
                }

                .logo {
                    display: block;
                    max-width: 180px;
                    max-height: 65px;
                    width: auto;
                    height: auto;
                }

                .tabela-itens {
                    width: 100%;
                    border-collapse: collapse;
                    table-layout: fixed;
                    font-size: 9px;
                }

                .tabela-itens th,
                .tabela-itens td {
                    border: 1px solid #777;
                    padding: 5px 4px;
                    vertical-align: top;
                    overflow-wrap: break-word;
                }

                .tabela-itens th {
                    background: #eeeeee;
                    font-weight: bold;
                }

                .tabela-itens th:nth-child(1) { width: 5%; }
                .tabela-itens th:nth-child(2) { width: 34%; }
                .tabela-itens th:nth-child(3) { width: 7%; }
                .tabela-itens th:nth-child(4) { width: 18%; }
                .tabela-itens th:nth-child(5) { width: 9%; }
                .tabela-itens th:nth-child(6) { width: 10%; }
                .tabela-itens th:nth-child(7) { width: 17%; }

                .tabela-itens thead {
                    display: table-header-group;
                }

                .tabela-itens tr {
                    page-break-inside: avoid;
                }

                /* TOTAIS */
                .totais {
                    page-break-inside: avoid;
                }

                .totais td {
                    padding: 4px;
                }

                /* OBSERVAÇÕES */
                .observacoes {
                    margin-top: 10px;
                    margin-bottom: 8px;
                    page-break-inside: avoid;
                }

                /* O RODAPÉ É DESENHADO NO CANVAS DO DOMPDF */
                hr {
                    margin: 5px 0;
                }

            </style>
            ";
        $html .= "</head>";
        $html .= "<body>";

        // CABEÇALHO (EMPRESA E CLIENTE)
        $html .= '<table width="100%" border="0" cellpadding="5" cellspacing="0">';
        $html .= '<tr style="font-size:12px;">';
        $html .= '<td width="65%" valign="top">';

        $logoHtml = $this->gerarLogoHtml(
            $dados['empresa_logo'] ?? ''
        );

        if ($logoHtml !== '') {
            $html .= '<div class="logo-container">';
            $html .= $logoHtml;
            $html .= '</div>';
        }

        $html .= "<b>$nomeEmpresa</b><br />";
        $html .= "$endereco_empresa<br />";
        $html .= "<b>Tel.:</b> $contacto_empresa<br/>";
        $html .= "<b>Nuit:</b> $nuit_empresa <br />";
        $html .= "<b>E-mail:</b> $emailT <br />";
        $html .= "<b>Web:</b> $website <br />";
        $html .= '</td>';
        $html .= '<td width="35%">Exmo.(s) Sr.(s) <br>';
        $html .= "$client_id<br />";
        $html .= "Nuit: $nuitClient<br/>";
        $html .= "Endereço: $enderecoClient<br/>";
        $html .= "Tel.: $telefoneClient<br/>";
        $html .= '</td></tr></table><br/>';

        $html .= '<hr class="featurette-divider">';

        // DATAS E TIPO DE DOCUMENTO
        $html .= '<table width="100%" border="0" cellpadding="5" cellspacing="0">';
        $html .= '<tr style="font-size:12px;">';
        $html .= '<td width="65%">';
        $html .= "Data do Documento: $fatura_date <br />";
        $html .= "Data do Vencimento: $fatura_datav <br />";
        $html .= "Prazo de Pagamento: <br />";
        $html .= "Moeda: <b style='text-transform: uppercase;'>$moeda</b><br/>";
        $html .= '</td>';
        $html .= '<td width="35%" align="center">';
        $html .= "$tipoFa: $codigo_fatura / $ano_fatura<br />";
        $html .= '</td>';
        $html .= '</tr>';
        $html .= '</table>';

        $html .= '<hr class="featurette-divider">';

        // ITENS DA COTAÇÃO
        $html .= '<table class="tabela-itens" width="100%" border="0" style="border-collapse: collapse;">';
        $html .= '<thead>';
        $html .= '<tr style="font-size:12px;">';
        $html .= '<th scope="col" align="left">Art</th>';
        $html .= '<th scope="col" align="left">Descrição</th>';
        $html .= '<th scope="col" align="left">Quant</th>';
        $html .= '<th scope="col" align="left">Preco Unit</th>';
        $html .= '<th scope="col" align="left">IVA</th>';
        $html .= '<th scope="col" align="left">Desc.(%)</th>';
        $html .= '<th scope="col" align="left">Total Liquido</th>';
        $html .= '</tr>';
        $html .= '</thead>';
        $html .= '<tbody>';

        $itens = $dados['itens'] ?? [];
        $count = 0;

        foreach ($itens as $item) {
            $count++;
            $produto = $this->esc($item['produto_nome'] ?? '');
            $qtd = $this->formatarQuantidade($item['quantidade'] ?? 0);
            $preco = $this->formatarMoeda($item['preco_unitario'] ?? 0);
            $ivaItem = $this->formatarNumero($item['imposto_percentual'] ?? 0) . '%';
            $descontoItem = $this->formatarNumero($item['desconto_percentual'] ?? 0);
            $totalLiquido = $this->formatarMoeda($item['total'] ?? 0);

            $html .= '<tr style="font-size:12px;">';
            $html .= '<td align="left">' . $count . '</td>';
            $html .= '<td align="left">' . $produto . '</td>';
            $html .= '<td align="left">' . $qtd . '</td>';
            $html .= '<td align="left">' . $preco . '</td>';
            $html .= '<td align="left">' . $ivaItem . '</td>';
            $html .= '<td align="left">' . $descontoItem . '</td>';
            $html .= '<td align="left">' . $totalLiquido . '</td>';
            $html .= '</tr>';
        }

        if ($count === 0) {
            $html .= '<tr style="font-size:12px;"><td colspan="7" align="center">Nenhum item encontrado.</td></tr>';
        }

        $html .= '</tbody>';

        // TOTAIS
        $html .= '<tfoot class="totais">';
        $html .= '<tr style="font-size:12px;">';
        $html .= '<td colspan="7" style="border: none;"></td>';
        $html .= '</tr>';
        $html .= '<tr style="font-size:12px;">';
        $html .= '<td colspan="5" style="border: none;"></td>';
        $html .= '<td align="left"><b>Subtotal</b></td>';
        $html .= "<td>$sub_total</td>";
        $html .= '</tr>';
        $html .= '<tr style="font-size:12px;">';
        $html .= '<td colspan="5" style="border: none;"></td>';
        $html .= '<td align="left"><b>Desconto</b></td>';
        $html .= "<td>$discount</td>";
        $html .= '</tr>';
        $html .= '<tr style="font-size:12px;">';
        $html .= '<td colspan="5" style="border: none;"></td>';
        $html .= '<td align="left"><b>IVA</b></td>';
        $html .= "<td>$ivaT</td>";
        $html .= '</tr>';
        $html .= '<tr style="font-size:12px;">';
        $html .= '<td colspan="5" style="border: none;"></td>';
        $html .= '<td align="left"><b>Total</b></td>';
        $html .= "<td>$total_amount</td>";
        $html .= '</tr>';
        $html .= '</tfoot>';
        $html .= '</table>';

        // =========================================================
        // OBSERVAÇÕES
        // =========================================================

        if (!empty($obs)) {

            $html .= '<div class="observacoes">';

            $html .= "<span style='font-size:12px;'>";
            $html .= "<b>Observações:</b><br>";
            $html .= nl2br($obs);
            $html .= "</span>";

            $html .= '</div>';
        }




        $html .= '</body>';
        $html .= '</html>';

        return $html;
    }

    /**
     * Desenha os dados bancários no rodapé
     * apenas da última página do PDF.
     */
    private function configurarRodapeBancario(
        \Dompdf\Dompdf $dompdf,
        array $dados
    ): void {

        $bancos = $dados['bancos'] ?? [];
        $moedaEmpresa = strtoupper((string)($dados['empresa_moeda'] ?? 'MZN'));

        $canvas = $dompdf->getCanvas();

        $canvas->page_script(
            function (
                $pageNumber,
                $pageCount,
                $canvas,
                $fontMetrics
            ) use (
                $bancos,
                $moedaEmpresa
            ): void {

                if ($pageNumber !== $pageCount) {
                    return;
                }

                $fontNormal = $fontMetrics->getFont('Times-Roman', 'normal');
                $fontBold = $fontMetrics->getFont('Times-Roman', 'bold');

                $larguraPagina = $canvas->get_width();
                $alturaPagina = $canvas->get_height();

                // Margens laterais de 15 mm (~42.5 pt)
                $esquerda = 42.5;
                $direita = $larguraPagina - 42.5;
                $larguraUtil = $direita - $esquerda;

                $numeroBancos = max(1, count($bancos));
                $alturaLinhas = $numeroBancos * 14;
                $alturaRodape = 55 + $alturaLinhas;

                $topo = $alturaPagina - 15 - $alturaRodape;
                $preto = [0, 0, 0];

                // 1. Título do rodapé
                $canvas->text(
                    $esquerda,
                    $topo,
                    'O pagamento pode ser feito por Depósito ou Transferência Bancária, Cheque',
                    $fontBold,
                    8.5,
                    $preto
                );

                // 2. Única linha separadora (Abaixo do título)
                $canvas->line(
                    $esquerda,
                    $topo + 16,
                    $direita,
                    $topo + 16,
                    $preto,
                    0.5
                );

                // Posições das colunas
                $colBanco = $esquerda;
                $colConta = $esquerda + ($larguraUtil * 0.30);
                $colIban  = $esquerda + ($larguraUtil * 0.58);
                $colMoeda = $esquerda + ($larguraUtil * 0.88);

                // 3. Cabeçalho da tabela
                $yCabecalho = $topo + 28;

                $canvas->text(
                    $colBanco,
                    $yCabecalho,
                    'Banco',
                    $fontBold,
                    8.5,
                    $preto
                );

                $canvas->text(
                    $colConta,
                    $yCabecalho,
                    'Conta',
                    $fontBold,
                    8.5,
                    $preto
                );

                $canvas->text(
                    $colIban,
                    $yCabecalho,
                    'NIB ',
                    $fontBold,
                    8.5,
                    $preto
                );

                $canvas->text(
                    $colMoeda,
                    $yCabecalho,
                    'Moeda',
                    $fontBold,
                    8.5,
                    $preto
                );

                // Sem contas bancárias
                if (empty($bancos)) {
                    $canvas->text(
                        $esquerda,
                        $topo + 44,
                        'Nenhuma conta bancária disponível.',
                        $fontNormal,
                        8.5,
                        $preto
                    );
                    return;
                }

                // 4. Impressão dos dados dos bancos
                $y = $topo + 44;
                $alturaLinha = 14;

                foreach ($bancos as $banco) {

                    $nomeBanco = trim((string)($banco['nome_banco'] ?? ''));
                    $conta     = trim((string)($banco['conta'] ?? ''));
                    $digito    = trim((string)($banco['digito_conta'] ?? ''));

                    if ($digito !== '') {
                        $conta .= '-' . $digito;
                    }

                    $iban  = trim((string)($banco['iban'] ?? ''));
                    $moeda = strtoupper((string)($banco['moeda'] ?? $moedaEmpresa));

                    // Truncar textos longos
                    $nomeBanco = mb_strimwidth($nomeBanco, 0, 24, '...', 'UTF-8');
                    $conta     = mb_strimwidth($conta, 0, 22, '...', 'UTF-8');
                    $iban      = $iban !== '' ? mb_strimwidth($iban, 0, 27, '...', 'UTF-8') : '-';

                    $canvas->text($colBanco, $y, $nomeBanco, $fontNormal, 8, $preto);
                    $canvas->text($colConta, $y, $conta, $fontNormal, 8, $preto);
                    $canvas->text($colIban,  $y, $iban, $fontNormal, 8, $preto);
                    $canvas->text($colMoeda, $y, $moeda, $fontNormal, 8, $preto);

                    $y += $alturaLinha;
                }
            }
        );
    }

    // =========================================================
    // LOGO
    // =========================================================

    private function gerarLogoHtml(
        mixed $logo
    ): string {

        $logo =
            trim(
                (string)$logo
            );

        if ($logo === '') {
            return '';
        }


        // -----------------------------------------------------
        // URL absoluta
        // -----------------------------------------------------

        if (
            preg_match(
                '#^https?://#i',
                $logo
            )
        ) {

            return '
                <img
                    src="'
                . $this->esc($logo)
                . '"
                    class="logo"
                    alt="Logo"
                >
            ';
        }


        // -----------------------------------------------------
        // Caminhos possíveis
        // -----------------------------------------------------

        $raizProjeto =
            realpath(
                __DIR__ . '/../..'
            );


        if (!$raizProjeto) {
            return '';
        }


        $candidatos = [

            $raizProjeto
                . '/public/uploads/empresas/'
                . $logo,

            $raizProjeto
                . '/public/uploads/empresa/'
                . $logo,

            $raizProjeto
                . '/uploads/empresas/'
                . $logo,

            $raizProjeto
                . '/uploads/empresa/'
                . $logo,

            $raizProjeto
                . '/public/assets/images/empresas/'
                . $logo,

            $raizProjeto
                . '/public/admin/dist/img/'
                . $logo,

            $raizProjeto
                . '/admin/dist/img/'
                . $logo
        ];


        foreach ($candidatos as $caminho) {

            if (
                is_file($caminho)
                && is_readable($caminho)
            ) {

                $conteudo =
                    file_get_contents(
                        $caminho
                    );

                if ($conteudo === false) {
                    continue;
                }


                $mime =
                    mime_content_type(
                        $caminho
                    );


                if (!$mime) {
                    $mime =
                        'image/jpeg';
                }


                $base64 =
                    base64_encode(
                        $conteudo
                    );


                return '
                    <img
                        src="data:'
                    . $mime
                    . ';base64,'
                    . $base64
                    . '"
                        class="logo"
                        alt="Logo"
                    >
                ';
            }
        }


        /*
         * Caso o sistema guarde o caminho relativo
         * e ainda não esteja numa das pastas acima,
         * tentamos gerar uma URL relativa.
         */

        $url =
            '/erpplus/'
            . ltrim(
                $logo,
                '/'
            );


        return '
            <img
                src="'
            . $this->esc($url)
            . '"
                class="logo"
                alt="Logo"
            >
        ';
    }


    // =========================================================
    // ENDEREÇO EMPRESA
    // =========================================================

    private function montarEnderecoEmpresa(
        string $logradouro,
        string $cidade,
        string $provincia,
        string $codigoPostal,
        string $pais
    ): string {

        $partes = [];

        if ($logradouro !== '') {
            $partes[] = $logradouro;
        }

        if ($cidade !== '') {
            $partes[] = $cidade;
        }

        if ($provincia !== '') {
            $partes[] = $provincia;
        }

        if ($codigoPostal !== '') {
            $partes[] =
                'CP ' . $codigoPostal;
        }

        if ($pais !== '') {
            $partes[] = $pais;
        }

        return $this->esc(
            implode(
                ', ',
                $partes
            )
        );
    }


    // =========================================================
    // FORMATAÇÃO DE DATA
    // =========================================================

    private function formatarData(
        mixed $valor
    ): string {

        if (
            $valor === null
            || trim((string)$valor) === ''
        ) {
            return '-';
        }

        $data =
            DateTime::createFromFormat(
                'Y-m-d',
                (string)$valor
            );

        if ($data === false) {
            return $this->esc(
                (string)$valor
            );
        }

        return $data->format(
            'd/m/Y'
        );
    }


    // =========================================================
    // FORMATAÇÃO DE MOEDA
    // =========================================================

    private function formatarMoeda(
        mixed $valor
    ): string {

        $numero =
            (float)$valor;

        return number_format(
            $numero,
            2,
            ',',
            '.'
        );
    }


    // =========================================================
    // FORMATAÇÃO DE NÚMERO
    // =========================================================

    private function formatarNumero(
        mixed $valor
    ): string {

        $numero =
            (float)$valor;

        return number_format(
            $numero,
            2,
            ',',
            '.'
        );
    }


    // =========================================================
    // FORMATAÇÃO DE QUANTIDADE
    // =========================================================

    private function formatarQuantidade(mixed $valor): string
    {
        $numero = (float)$valor;

        if ($numero == (int)$numero) {
            return number_format($numero, 0, ',', '.');
        }

        return number_format($numero, 2, ',', '.');
    }


    // =========================================================
    // ESCAPAR HTML
    // =========================================================

    private function esc(
        mixed $valor
    ): string {

        return htmlspecialchars(
            (string)(
                $valor
                ?? ''
            ),
            ENT_QUOTES,
            'UTF-8'
        );
    }


    // =========================================================
    // RESPOSTA PDF DE ERRO
    // =========================================================

    private function respostaPdfErro(
        string $mensagem
    ): never {

        if (
            !headers_sent()
        ) {

            http_response_code(500);

            header(
                'Content-Type: text/html; charset=utf-8'
            );
        }

        echo '
            <!DOCTYPE html>
            <html lang="pt">
            <head>
                <meta charset="UTF-8">
                <title>Erro</title>
                <style>
                    body {
                        font-family: Arial, sans-serif;
                        padding: 40px;
                    }

                    .erro {
                        border: 1px solid #dc3545;
                        background: #f8d7da;
                        color: #721c24;
                        padding: 20px;
                        border-radius: 5px;
                    }
                </style>
            </head>

            <body>

                <div class="erro">
                    <strong>Não foi possível gerar a cotação.</strong>
                    <br><br>
                    '
            . $this->esc($mensagem)
            . '
                </div>

            </body>
            </html>
        ';

        exit;
    }


    // =========================================================
    // STATUS
    // =========================================================

    public function status(): array
    {
        try {

            return [
                'sucesso' => true,

                'dados' =>
                $this->model
                    ->listarStatus()
            ];
        } catch (Throwable $e) {

            return $this->erro($e);
        }
    }


    // =========================================================
    // PESQUISAR CLIENTES
    // =========================================================

    public function clientes(): array
    {
        try {

            $termo =
                trim(
                    (string)(
                        $_GET['termo']
                        ?? $_POST['termo']
                        ?? ''
                    )
                );

            return [
                'sucesso' => true,

                'dados' =>
                $this->model
                    ->pesquisarClientes(
                        $this->empresaId(),
                        $termo
                    )
            ];
        } catch (Throwable $e) {

            return $this->erro($e);
        }
    }


    // =========================================================
    // PESQUISAR PRODUTOS
    // =========================================================

    public function produtos(): array
    {
        try {

            $termo =
                trim(
                    (string)(
                        $_GET['termo']
                        ?? $_POST['termo']
                        ?? ''
                    )
                );

            return [
                'sucesso' => true,

                'dados' =>
                $this->model
                    ->pesquisarProdutos(
                        $this->empresaId(),
                        $termo
                    )
            ];
        } catch (Throwable $e) {

            return $this->erro($e);
        }
    }


    // =========================================================
    // GERAR NÚMERO
    // =========================================================

    public function gerarNumero(): array
    {
        try {

            return [
                'sucesso' => true,

                'dados' => [
                    'numero' =>
                    $this->model
                        ->gerarProximoNumero(
                            $this->empresaId()
                        )
                ]
            ];
        } catch (Throwable $e) {

            return $this->erro($e);
        }
    }


    // =========================================================
    // SALVAR
    // =========================================================

    public function salvar(): array
    {
        try {

            $empresaId =
                $this->empresaId();

            $usuarioId =
                $this->usuarioId();

            $id =
                $this->inteiroOpcional(
                    $_POST['id']
                        ?? null
                );


            // =================================================
            // COTAÇÃO ATUAL
            // =================================================

            $cotacaoAntes =
                null;

            if ($id !== null) {

                $cotacaoAntes =
                    $this->model->buscar(
                        $id,
                        $empresaId
                    );

                if (!$cotacaoAntes) {

                    return [
                        'sucesso' => false,

                        'mensagem' =>
                        'Cotação não encontrada.'
                    ];
                }
            }


            // =================================================
            // NÚMERO
            // =================================================

            $numero =
                trim(
                    (string)(
                        $_POST['numero']
                        ?? ''
                    )
                );

            if ($numero === '') {

                $numero =
                    $this->model
                    ->gerarProximoNumero(
                        $empresaId
                    );
            }

            if (
                mb_strlen($numero)
                > 50
            ) {

                throw new InvalidArgumentException(
                    'O número da cotação é demasiado longo.'
                );
            }


            if (
                $this->model->numeroExiste(
                    $empresaId,
                    $numero,
                    $id
                )
            ) {

                return [
                    'sucesso' => false,

                    'mensagem' =>
                    'Já existe uma cotação com este número.'
                ];
            }


            // =================================================
            // DATA
            // =================================================

            $dataEmissao =
                trim(
                    (string)(
                        $_POST['data_emissao']
                        ?? ''
                    )
                );

            if (
                !$this->dataValida(
                    $dataEmissao
                )
            ) {

                throw new InvalidArgumentException(
                    'Informe uma data de emissão válida.'
                );
            }


            // =================================================
            // VALIDADE
            // =================================================

            $validade =
                trim(
                    (string)(
                        $_POST['validade']
                        ?? ''
                    )
                );

            if ($validade === '') {

                $validade = null;
            } elseif (
                !$this->dataValida(
                    $validade
                )
            ) {

                throw new InvalidArgumentException(
                    'A data de validade é inválida.'
                );
            }


            if (
                $validade !== null
                && $validade < $dataEmissao
            ) {

                throw new InvalidArgumentException(
                    'A validade da cotação não pode ser anterior à data de emissão.'
                );
            }


            // =================================================
            // STATUS
            // =================================================

            $statusId =
                $this->inteiroObrigatorio(
                    $_POST['status_id']
                        ?? null,
                    'Selecione o estado da cotação.'
                );

            if (
                !$this->model->statusExiste(
                    $statusId
                )
            ) {

                return [
                    'sucesso' => false,

                    'mensagem' =>
                    'O estado selecionado não pertence às cotações.'
                ];
            }


            // =================================================
            // CLIENTE
            // =================================================

            $clienteId =
                $this->inteiroOpcional(
                    $_POST['cliente_id']
                        ?? null
                );

            $clienteNome =
                $this->textoObrigatorio(
                    $_POST['cliente_nome']
                        ?? null,
                    'Informe o nome do cliente.',
                    150
                );

            $clienteNuit =
                $this->textoOpcional(
                    $_POST['cliente_nuit']
                        ?? null,
                    30
                );

            $clienteTelefone =
                $this->textoOpcional(
                    $_POST['cliente_telefone']
                        ?? null,
                    30
                );

            $clienteEndereco =
                $this->textoOpcional(
                    $_POST['cliente_endereco']
                        ?? null,
                    255
                );


            if ($clienteId !== null) {

                $cliente =
                    $this->clienteModel
                    ->buscar(
                        $clienteId,
                        $empresaId
                    );

                if (!$cliente) {

                    return [
                        'sucesso' => false,

                        'mensagem' =>
                        'O cliente selecionado não pertence à empresa ou não existe.'
                    ];
                }


                $clienteNome =
                    $this->textoObrigatorio(
                        $cliente['nome']
                            ?? null,
                        'O cliente selecionado não possui nome.',
                        150
                    );

                $clienteNuit =
                    $this->textoOpcional(
                        $cliente['nuit']
                            ?? null,
                        30
                    );

                $clienteTelefone =
                    $this->textoOpcional(
                        $cliente['telefone']
                            ?? null,
                        30
                    );

                $clienteEndereco =
                    $this->textoOpcional(
                        $cliente['endereco']
                            ?? null,
                        255
                    );
            }


            // =================================================
            // ITENS
            // =================================================

            $itensBrutos =
                $_POST['itens']
                ?? [];

            if (
                is_string(
                    $itensBrutos
                )
            ) {

                $itensBrutos =
                    json_decode(
                        $itensBrutos,
                        true
                    );
            }

            if (
                !is_array(
                    $itensBrutos
                )
            ) {

                throw new InvalidArgumentException(
                    'Os itens da cotação são inválidos.'
                );
            }

            if (
                count($itensBrutos) === 0
            ) {

                throw new InvalidArgumentException(
                    'Adicione pelo menos um item à cotação.'
                );
            }


            // =================================================
            // PROCESSAR ITENS
            // =================================================

            $itens = [];

            $subtotalGeral =
                0.00;

            $descontoValorGeral =
                0.00;

            $impostoValorGeral =
                0.00;


            foreach (
                $itensBrutos as $indice => $item
            ) {

                if (
                    !is_array($item)
                ) {
                    continue;
                }


                $produtoId =
                    $this->inteiroOpcional(
                        $item['produto_id']
                            ?? null
                    );


                $produtoSku =
                    null;

                $produtoNome =
                    $this->textoObrigatorio(
                        $item['produto_nome']
                            ?? null,
                        'Informe o nome do produto do item '
                            . ($indice + 1)
                            . '.',
                        150
                    );


                $produtoDescricao =
                    $this->textoLivreOpcional(
                        $item['produto_descricao']
                            ?? null
                    );


                $unidade =
                    $this->textoOpcional(
                        $item['unidade']
                            ?? null,
                        20
                    ) ?? 'UN';


                if (
                    $produtoId !== null
                ) {

                    $produto =
                        $this->produtoModel
                        ->buscar(
                            $produtoId,
                            $empresaId
                        );

                    if (!$produto) {

                        throw new InvalidArgumentException(
                            'O produto selecionado no item '
                                . ($indice + 1)
                                . ' não pertence à empresa.'
                        );
                    }


                    $produtoSku =
                        $produto['sku']
                        ?? null;

                    $produtoNome =
                        $produto['nome']
                        ?? $produtoNome;

                    $produtoDescricao =
                        $produto['descricao']
                        ?? $produtoDescricao;

                    $unidade =
                        $produto['unidade_codigo']
                        ?? $produto['unidade_nome']
                        ?? $unidade;
                }


                $quantidade =
                    $this->decimalPositivo(
                        $item['quantidade']
                            ?? 0,
                        3,
                        'A quantidade do item '
                            . ($indice + 1)
                            . ' é inválida.'
                    );


                $precoUnitario =
                    $this->decimalNaoNegativo(
                        $item['preco_unitario']
                            ?? 0,
                        2,
                        'O preço do item '
                            . ($indice + 1)
                            . ' é inválido.'
                    );


                $descontoPercentual =
                    $this->decimalPercentual(
                        $item['desconto_percentual']
                            ?? 0,
                        'O desconto do item '
                            . ($indice + 1)
                            . ' é inválido.'
                    );


                $impostoPercentual =
                    $this->decimalPercentual(
                        $item['imposto_percentual']
                            ?? 0,
                        'O imposto do item '
                            . ($indice + 1)
                            . ' é inválido.'
                    );


                $subtotal =
                    round(
                        $quantidade
                            * $precoUnitario,
                        2
                    );


                $descontoValor =
                    round(
                        $subtotal
                            * (
                                $descontoPercentual
                                / 100
                            ),
                        2
                    );


                $baseTributavel =
                    round(
                        $subtotal
                            - $descontoValor,
                        2
                    );


                $impostoValor =
                    round(
                        $baseTributavel
                            * (
                                $impostoPercentual
                                / 100
                            ),
                        2
                    );


                $total =
                    round(
                        $baseTributavel
                            + $impostoValor,
                        2
                    );


                $subtotalGeral +=
                    $subtotal;

                $descontoValorGeral +=
                    $descontoValor;

                $impostoValorGeral +=
                    $impostoValor;


                $itens[] = [

                    'produto_id' =>
                    $produtoId,

                    'produto_sku' =>
                    $produtoSku,

                    'produto_nome' =>
                    $produtoNome,

                    'produto_descricao' =>
                    $produtoDescricao,

                    'unidade' =>
                    $unidade,

                    'quantidade' =>
                    $quantidade,

                    'preco_unitario' =>
                    $precoUnitario,

                    'subtotal' =>
                    number_format(
                        $subtotal,
                        2,
                        '.',
                        ''
                    ),

                    'desconto_percentual' =>
                    $descontoPercentual,

                    'desconto_valor' =>
                    number_format(
                        $descontoValor,
                        2,
                        '.',
                        ''
                    ),

                    'base_tributavel' =>
                    number_format(
                        $baseTributavel,
                        2,
                        '.',
                        ''
                    ),

                    'imposto_percentual' =>
                    $impostoPercentual,

                    'imposto_valor' =>
                    number_format(
                        $impostoValor,
                        2,
                        '.',
                        ''
                    ),

                    'total' =>
                    number_format(
                        $total,
                        2,
                        '.',
                        ''
                    ),

                    'ordem' =>
                    count($itens) + 1
                ];
            }


            // =================================================
            // TOTAIS
            // =================================================

            $subtotalGeral =
                round(
                    $subtotalGeral,
                    2
                );

            $descontoValorGeral =
                round(
                    $descontoValorGeral,
                    2
                );

            $impostoValorGeral =
                round(
                    $impostoValorGeral,
                    2
                );


            $descontoPercentualGeral =
                $subtotalGeral > 0
                ? round(
                    (
                        $descontoValorGeral
                        / $subtotalGeral
                    ) * 100,
                    2
                )
                : 0.00;


            $baseGeral =
                round(
                    $subtotalGeral
                        - $descontoValorGeral,
                    2
                );


            $impostoPercentualGeral =
                $baseGeral > 0
                ? round(
                    (
                        $impostoValorGeral
                        / $baseGeral
                    ) * 100,
                    2
                )
                : 0.00;


            $totalGeral =
                round(
                    $baseGeral
                        + $impostoValorGeral,
                    2
                );


            // =================================================
            // OBSERVAÇÕES
            // =================================================

            $observacoes =
                $this->textoLivreOpcional(
                    $_POST['observacoes']
                        ?? null
                );


            // =================================================
            // DADOS FINAIS
            // =================================================

            $dados = [

                'cliente_id' =>
                $clienteId,

                'numero' =>
                $numero,

                'data_emissao' =>
                $dataEmissao,

                'validade' =>
                $validade,

                'cliente_nome' =>
                $clienteNome,

                'cliente_nuit' =>
                $clienteNuit,

                'cliente_telefone' =>
                $clienteTelefone,

                'cliente_endereco' =>
                $clienteEndereco,

                'subtotal' =>
                number_format(
                    $subtotalGeral,
                    2,
                    '.',
                    ''
                ),

                'desconto_percentual' =>
                number_format(
                    $descontoPercentualGeral,
                    2,
                    '.',
                    ''
                ),

                'desconto_valor' =>
                number_format(
                    $descontoValorGeral,
                    2,
                    '.',
                    ''
                ),

                'imposto_percentual' =>
                number_format(
                    $impostoPercentualGeral,
                    2,
                    '.',
                    ''
                ),

                'imposto_valor' =>
                number_format(
                    $impostoValorGeral,
                    2,
                    '.',
                    ''
                ),

                'total' =>
                number_format(
                    $totalGeral,
                    2,
                    '.',
                    ''
                ),

                'observacoes' =>
                $observacoes,

                'status_id' =>
                $statusId,

                'criado_por' =>
                $usuarioId
            ];


            // =================================================
            // ATUALIZAR
            // =================================================

            if ($id !== null) {

                $this->db
                    ->beginTransaction();

                try {

                    $atualizado =
                        $this->model
                        ->atualizar(
                            $id,
                            $empresaId,
                            $dados,
                            $itens
                        );

                    if (!$atualizado) {

                        throw new RuntimeException(
                            'Não foi possível atualizar a cotação.'
                        );
                    }


                    $cotacaoDepois =
                        $this->model->buscar(
                            $id,
                            $empresaId
                        );

                    if (!$cotacaoDepois) {

                        throw new RuntimeException(
                            'Não foi possível obter a cotação atualizada.'
                        );
                    }


                    $this->auditoria
                        ->atualizacao(
                            $empresaId,
                            $usuarioId,
                            'cotacoes',
                            $id,

                            $this->dadosAuditoriaCotacao(
                                $cotacaoAntes
                            ),

                            $this->dadosAuditoriaCotacao(
                                $cotacaoDepois
                            )
                        );


                    $this->log
                        ->registrar(
                            $empresaId,
                            $usuarioId,
                            'COTACOES',
                            'EDITAR',

                            'Atualizou a cotação '
                                . $cotacaoDepois['numero']
                                . '.',

                            $id,
                            null
                        );


                    $this->db
                        ->commit();


                    return [
                        'sucesso' => true,

                        'mensagem' =>
                        'Cotação atualizada com sucesso.',

                        'dados' => [
                            'id' =>
                            $id
                        ]
                    ];
                } catch (Throwable $e) {

                    if (
                        $this->db
                        ->inTransaction()
                    ) {

                        $this->db
                            ->rollBack();
                    }

                    throw $e;
                }
            }


            // =================================================
            // CRIAR
            // =================================================

            $this->db
                ->beginTransaction();

            try {

                $novoId =
                    $this->model->criar(
                        $empresaId,
                        $dados,
                        $itens
                    );


                $cotacaoNova =
                    $this->model->buscar(
                        $novoId,
                        $empresaId
                    );

                if (!$cotacaoNova) {

                    throw new RuntimeException(
                        'Não foi possível obter a cotação criada.'
                    );
                }


                $this->auditoria
                    ->insercao(
                        $empresaId,
                        $usuarioId,
                        'cotacoes',
                        $novoId,

                        $this->dadosAuditoriaCotacao(
                            $cotacaoNova
                        )
                    );


                $this->log
                    ->registrar(
                        $empresaId,
                        $usuarioId,
                        'COTACOES',
                        'CRIAR',

                        'Criou a cotação '
                            . $cotacaoNova['numero']
                            . '.',

                        $novoId,
                        null
                    );


                $this->db
                    ->commit();


                return [
                    'sucesso' => true,

                    'mensagem' =>
                    'Cotação criada com sucesso.',

                    'dados' => [
                        'id' =>
                        $novoId,

                        'numero' =>
                        $cotacaoNova['numero']
                    ]
                ];
            } catch (Throwable $e) {

                if (
                    $this->db
                    ->inTransaction()
                ) {

                    $this->db
                        ->rollBack();
                }

                throw $e;
            }
        } catch (PDOException $e) {

            return $this->erroBanco($e);
        } catch (Throwable $e) {

            return $this->erro($e);
        }
    }


    // =========================================================
    // ELIMINAR
    // =========================================================

    public function eliminar(): array
    {
        try {

            $empresaId =
                $this->empresaId();

            $usuarioId =
                $this->usuarioId();

            $id =
                $this->inteiroObrigatorio(
                    $_POST['id']
                        ?? null,
                    'Cotação inválida.'
                );


            $cotacao =
                $this->model->buscar(
                    $id,
                    $empresaId
                );

            if (!$cotacao) {

                return [
                    'sucesso' => false,

                    'mensagem' =>
                    'Cotação não encontrada.'
                ];
            }


            $antes =
                $this->dadosAuditoriaCotacao(
                    $cotacao
                );


            $this->db
                ->beginTransaction();

            try {

                $eliminado =
                    $this->model->eliminar(
                        $id,
                        $empresaId
                    );

                if (!$eliminado) {

                    throw new RuntimeException(
                        'Não foi possível eliminar a cotação.'
                    );
                }


                $this->auditoria
                    ->exclusao(
                        $empresaId,
                        $usuarioId,
                        'cotacoes',
                        $id,
                        $antes
                    );


                $this->log
                    ->registrar(
                        $empresaId,
                        $usuarioId,
                        'COTACOES',
                        'ELIMINAR',

                        'Eliminou a cotação '
                            . $cotacao['numero']
                            . '.',

                        $id,
                        null
                    );


                $this->db
                    ->commit();


                return [
                    'sucesso' => true,

                    'mensagem' =>
                    'Cotação eliminada com sucesso.'
                ];
            } catch (Throwable $e) {

                if (
                    $this->db
                    ->inTransaction()
                ) {

                    $this->db
                        ->rollBack();
                }

                throw $e;
            }
        } catch (PDOException $e) {

            if (
                (string)$e->getCode()
                === '23000'
            ) {

                return [
                    'sucesso' => false,

                    'mensagem' =>
                    'A cotação possui registos associados e não pode ser eliminada.'
                ];
            }

            return $this->erroBanco($e);
        } catch (Throwable $e) {

            return $this->erro($e);
        }
    }


    // =========================================================
    // AUDITORIA
    // =========================================================

    private function dadosAuditoriaCotacao(
        ?array $cotacao
    ): array {

        if (!$cotacao) {
            return [];
        }

        return [

            'id' =>
            isset($cotacao['id'])
                ? (int)$cotacao['id']
                : null,

            'empresa_id' =>
            isset($cotacao['empresa_id'])
                ? (int)$cotacao['empresa_id']
                : null,

            'cliente_id' =>
            isset($cotacao['cliente_id'])
                && $cotacao['cliente_id'] !== null
                ? (int)$cotacao['cliente_id']
                : null,

            'numero' =>
            $cotacao['numero']
                ?? null,

            'data_emissao' =>
            $cotacao['data_emissao']
                ?? null,

            'validade' =>
            $cotacao['validade']
                ?? null,

            'cliente_nome' =>
            $cotacao['cliente_nome']
                ?? null,

            'cliente_nuit' =>
            $cotacao['cliente_nuit']
                ?? null,

            'cliente_telefone' =>
            $cotacao['cliente_telefone']
                ?? null,

            'cliente_endereco' =>
            $cotacao['cliente_endereco']
                ?? null,

            'subtotal' =>
            $cotacao['subtotal']
                ?? null,

            'desconto_percentual' =>
            $cotacao['desconto_percentual']
                ?? null,

            'desconto_valor' =>
            $cotacao['desconto_valor']
                ?? null,

            'imposto_percentual' =>
            $cotacao['imposto_percentual']
                ?? null,

            'imposto_valor' =>
            $cotacao['imposto_valor']
                ?? null,

            'total' =>
            $cotacao['total']
                ?? null,

            'observacoes' =>
            $cotacao['observacoes']
                ?? null,

            'status_id' =>
            isset($cotacao['status_id'])
                ? (int)$cotacao['status_id']
                : null,

            'itens' =>
            $cotacao['itens']
                ?? []
        ];
    }


    // =========================================================
    // EMPRESA
    // =========================================================

    private function empresaId(): int
    {
        $empresaId =
            $_SESSION['empresa_id']
            ?? $_SESSION['usuario']['empresa_id']
            ?? $_SESSION['user']['empresa_id']
            ?? null;

        if (
            $empresaId === null
            || !is_numeric($empresaId)
            || (int)$empresaId <= 0
        ) {

            throw new RuntimeException(
                'Empresa não identificada na sessão.'
            );
        }

        return (int)$empresaId;
    }


    // =========================================================
    // UTILIZADOR
    // =========================================================

    private function usuarioId(): int
    {
        $usuarioId =
            Auth::usuarioId()
            ?? (
                $_SESSION['usuario_id']
                ?? $_SESSION['utilizador_id']
                ?? $_SESSION['usuario']['id']
                ?? $_SESSION['user']['id']
                ?? null
            );

        if (
            $usuarioId === null
            || !is_numeric($usuarioId)
            || (int)$usuarioId <= 0
        ) {

            throw new RuntimeException(
                'Utilizador não identificado na sessão.'
            );
        }

        return (int)$usuarioId;
    }


    // =========================================================
    // INTEIRO OBRIGATÓRIO
    // =========================================================

    private function inteiroObrigatorio(
        mixed $valor,
        string $mensagem
    ): int {

        $numero =
            filter_var(
                $valor,
                FILTER_VALIDATE_INT
            );

        if (
            $numero === false
            || $numero <= 0
        ) {

            throw new InvalidArgumentException(
                $mensagem
            );
        }

        return (int)$numero;
    }


    // =========================================================
    // INTEIRO OPCIONAL
    // =========================================================

    private function inteiroOpcional(
        mixed $valor
    ): ?int {

        if (
            $valor === null
            || trim((string)$valor) === ''
        ) {

            return null;
        }

        return $this->inteiroObrigatorio(
            $valor,
            'Identificador inválido.'
        );
    }


    // =========================================================
    // TEXTO OBRIGATÓRIO
    // =========================================================

    private function textoObrigatorio(
        mixed $valor,
        string $mensagem,
        int $limite
    ): string {

        $texto =
            trim(
                (string)$valor
            );

        if ($texto === '') {

            throw new InvalidArgumentException(
                $mensagem
            );
        }

        if (
            mb_strlen($texto)
            > $limite
        ) {

            throw new InvalidArgumentException(
                'O valor informado excede o limite permitido.'
            );
        }

        return $texto;
    }


    // =========================================================
    // TEXTO OPCIONAL
    // =========================================================

    private function textoOpcional(
        mixed $valor,
        int $limite
    ): ?string {

        $texto =
            trim(
                (string)$valor
            );

        if ($texto === '') {
            return null;
        }

        if (
            mb_strlen($texto)
            > $limite
        ) {

            throw new InvalidArgumentException(
                'O valor informado excede o limite permitido.'
            );
        }

        return $texto;
    }


    // =========================================================
    // TEXTO LIVRE
    // =========================================================

    private function textoLivreOpcional(
        mixed $valor
    ): ?string {

        $texto =
            trim(
                (string)$valor
            );

        return $texto === ''
            ? null
            : $texto;
    }


    // =========================================================
    // DECIMAL NÃO NEGATIVO
    // =========================================================

    private function decimalNaoNegativo(
        mixed $valor,
        int $casasDecimais,
        string $mensagem
    ): string {

        $texto =
            str_replace(
                ',',
                '.',
                trim(
                    (string)$valor
                )
            );

        if (
            $texto === ''
            || !is_numeric($texto)
            || (float)$texto < 0
        ) {

            throw new InvalidArgumentException(
                $mensagem
            );
        }

        return number_format(
            (float)$texto,
            $casasDecimais,
            '.',
            ''
        );
    }


    // =========================================================
    // DECIMAL POSITIVO
    // =========================================================

    private function decimalPositivo(
        mixed $valor,
        int $casasDecimais,
        string $mensagem
    ): string {

        $texto =
            str_replace(
                ',',
                '.',
                trim(
                    (string)$valor
                )
            );

        if (
            $texto === ''
            || !is_numeric($texto)
            || (float)$texto <= 0
        ) {

            throw new InvalidArgumentException(
                $mensagem
            );
        }

        return number_format(
            (float)$texto,
            $casasDecimais,
            '.',
            ''
        );
    }


    // =========================================================
    // PERCENTAGEM
    // =========================================================

    private function decimalPercentual(
        mixed $valor,
        string $mensagem
    ): string {

        $texto =
            str_replace(
                ',',
                '.',
                trim(
                    (string)$valor
                )
            );

        if ($texto === '') {
            $texto = '0';
        }

        if (
            !is_numeric($texto)
            || (float)$texto < 0
            || (float)$texto > 100
        ) {

            throw new InvalidArgumentException(
                $mensagem
            );
        }

        return number_format(
            (float)$texto,
            2,
            '.',
            ''
        );
    }


    // =========================================================
    // DATA
    // =========================================================

    private function dataValida(
        string $data
    ): bool {

        $data =
            trim($data);

        if ($data === '') {
            return false;
        }

        $dt =
            DateTime::createFromFormat(
                'Y-m-d',
                $data
            );

        return $dt !== false
            && $dt->format('Y-m-d')
            === $data;
    }


    // =========================================================
    // ERRO BANCO
    // =========================================================

    private function erroBanco(
        PDOException $e
    ): array {

        error_log(
            sprintf(
                '[CotacaoController] %s em %s:%d',
                $e->getMessage(),
                $e->getFile(),
                $e->getLine()
            )
        );

        $mensagem =
            mb_strtolower(
                $e->getMessage()
            );


        if (
            str_contains(
                $mensagem,
                'uk_cotacoes_empresa_numero'
            )
            || (
                str_contains(
                    $mensagem,
                    'duplicate'
                )
                && str_contains(
                    $mensagem,
                    'numero'
                )
            )
        ) {

            return [
                'sucesso' => false,

                'mensagem' =>
                'Já existe uma cotação com este número.'
            ];
        }


        return [
            'sucesso' => false,

            'mensagem' =>
            'Não foi possível processar os dados da cotação.'
        ];
    }


    // =========================================================
    // ERRO GERAL
    // =========================================================

    private function erro(
        Throwable $e
    ): array {

        error_log(
            sprintf(
                '[CotacaoController] %s em %s:%d',
                $e->getMessage(),
                $e->getFile(),
                $e->getLine()
            )
        );


        return [
            'sucesso' => false,

            'mensagem' =>
            $e instanceof InvalidArgumentException
                || $e instanceof RuntimeException
                ? $e->getMessage()
                : 'Ocorreu um erro interno ao processar a cotação.'
        ];
    }
}
