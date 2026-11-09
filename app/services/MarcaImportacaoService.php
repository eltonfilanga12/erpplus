<?php

declare(strict_types=1);

final class MarcaImportacaoService
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function gerarModeloExcel(int $empresaId): \PhpOffice\PhpSpreadsheet\Spreadsheet
    {
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Modelo Marcas');

        $cabecalhos = [
            'Nome',
            'Descrição'
        ];

        $coluna = 'A';
        foreach ($cabecalhos as $cabecalho) {
            $sheet->setCellValue($coluna . '1', $cabecalho);
            $sheet->getStyle($coluna . '1')->getFont()->setBold(true);
            $coluna++;
        }

        return $spreadsheet;
    }

    public function validarArquivo(array $ficheiro, int $empresaId, int $usuarioId): array
    {
        if (!isset($ficheiro['tmp_name']) || !is_uploaded_file($ficheiro['tmp_name'])) {
            throw new RuntimeException('Ficheiro inválido ou não enviado.');
        }

        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($ficheiro['tmp_name']);
        $sheet = $spreadsheet->getActiveSheet();
        $linhas = $sheet->toArray(null, true, true, true);

        if (count($linhas) <= 1) {
            throw new RuntimeException('O ficheiro Excel está vazio ou contém apenas o cabeçalho.');
        }

        $stmtStatus = $this->db->prepare("SELECT id FROM status WHERE tipo = 'marca' AND LOWER(nome) IN ('activo', 'ativo', 'activa', 'ativa') LIMIT 1");
        $stmtStatus->execute();
        $statusAtivoId = $stmtStatus->fetchColumn();

        if (!$statusAtivoId) {
            throw new RuntimeException('Estado ativo para marcas não encontrado na base de dados.');
        }

        $stmtExistentes = $this->db->prepare("SELECT nome FROM marcas WHERE empresa_id = :empresa_id");
        $stmtExistentes->execute([':empresa_id' => $empresaId]);
        $marcasBD = [];
        while ($row = $stmtExistentes->fetch(PDO::FETCH_ASSOC)) {
            $marcasBD[mb_strtolower(trim($row['nome']))] = true;
        }

        $validas = [];
        $erros = [];
        $nomesNoArquivo = [];

        foreach ($linhas as $indice => $linha) {
            if ($indice === 1) {
                continue; // Ignorar cabeçalho
            }

            $nome = trim((string)($linha['A'] ?? ''));
            if ($nome === '') {
                continue;
            }

            $errosLinha = [];
            $descricao = trim((string)($linha['B'] ?? '')) ?: null;

            if (mb_strlen($nome) > 120) {
                $errosLinha[] = 'O nome não pode exceder 120 caracteres.';
            }

            if ($descricao !== null && mb_strlen($descricao) > 500) {
                $errosLinha[] = 'A descrição não pode exceder 500 caracteres.';
            }

            $nomeLower = mb_strtolower($nome);
            if (isset($nomesNoArquivo[$nomeLower])) {
                $errosLinha[] = "O nome '{$nome}' está duplicado no próprio ficheiro.";
            } else {
                $nomesNoArquivo[$nomeLower] = true;
                if (isset($marcasBD[$nomeLower])) {
                    $errosLinha[] = "Já existe uma marca registada com o nome '{$nome}'.";
                }
            }

            $dadosLinha = [
                'status_id' => (int)$statusAtivoId,
                'nome' => $nome,
                'descricao' => $descricao
            ];

            if (empty($errosLinha)) {
                $validas[] = [
                    'linha' => $indice,
                    'dados' => $dadosLinha,
                    'preview' => [
                        'nome' => $nome,
                        'descricao' => $descricao ?: '-'
                    ]
                ];
            } else {
                $erros[] = [
                    'linha' => $indice,
                    'dados' => ['nome' => $nome],
                    'erros' => $errosLinha
                ];
            }
        }

        $token = bin2hex(random_bytes(32));
        $_SESSION['importacao_marcas_' . $token] = $validas;

        return [
            'token' => $token,
            'total_linhas' => count($validas) + count($erros),
            'total_validos' => count($validas),
            'total_erros' => count($erros),
            'validos' => $validas,
            'erros' => $erros
        ];
    }

    public function confirmar(string $token, int $empresaId, int $usuarioId): array
    {
        $sessaoChave = 'importacao_marcas_' . $token;
        if (!isset($_SESSION[$sessaoChave]) || !is_array($_SESSION[$sessaoChave])) {
            throw new RuntimeException('A sessão de importação expirou ou é inválida.');
        }

        $validas = $_SESSION[$sessaoChave];
        unset($_SESSION[$sessaoChave]);

        if (empty($validas)) {
            throw new RuntimeException('Não existem registos válidos para importar.');
        }

        $marcaModel = new Marca($this->db);
        $criados = [];

        $this->db->beginTransaction();
        try {
            foreach ($validas as $item) {
                $dados = $item['dados'];
                $novoId = $marcaModel->criar($empresaId, $dados);
                $criados[] = [
                    'id' => $novoId,
                    'nome' => $dados['nome'],
                    'linha' => $item['linha']
                ];
            }
            $this->db->commit();
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }

        return [
            'total' => count($criados),
            'produtos' => $criados
        ];
    }
}
