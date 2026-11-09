<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| EXPORTAÇÃO DOS RELATÓRIOS DE RH PARA EXCEL
|--------------------------------------------------------------------------
|
| Esta página reutiliza integralmente a geração da impressão. Assim, a
| impressão e a exportação consultam o mesmo controller, aplicam os mesmos
| filtros e apresentam exatamente os mesmos dados.
|
*/

$tipo = strtoupper(
    trim(
        (string)($_GET['tipo'] ?? '')
    )
);

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

$nomeArquivo = strtolower(
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

/*
|--------------------------------------------------------------------------
| GERAR O MESMO CONTEÚDO DA IMPRESSÃO
|--------------------------------------------------------------------------
*/

ob_start();

require __DIR__
    . '/relatorios_rh_imprimir.php';

$conteudo = (string)ob_get_clean();

/* Remover o botão de impressão da folha Excel. */
$conteudo = (string)preg_replace(
    '/<div\s+class="acoes">.*?<\/div>/si',
    '',
    $conteudo
);

/*
|--------------------------------------------------------------------------
| CABEÇALHOS DO EXCEL
|--------------------------------------------------------------------------
*/

header(
    'Content-Type: application/vnd.ms-excel; charset=UTF-8'
);

header(
    'Content-Disposition: attachment; filename="'
        . $nomeArquivo
        . '"'
);

header('Cache-Control: max-age=0');
header('Pragma: public');

/* BOM UTF-8 para o Excel reconhecer corretamente os acentos. */
echo "\xEF\xBB\xBF";
echo $conteudo;
