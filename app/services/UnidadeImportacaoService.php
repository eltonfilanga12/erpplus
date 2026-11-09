<?php

declare(strict_types=1);

final class UnidadeImportacaoService
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
        $sheet->setTitle('Modelo Unidades');

        $cabecalhos = [
            'Código',
            'Nome',
            'Casas Decimais',
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

        $stmtStatus = $this->db->prepare("SELECT id FROM status WHERE tipo = 'unidade' AND LOWER(nome) IN ('activo', 'ativo', 'activa', 'ativa') LIMIT 1");
        $stmtStatus->execute();
        $statusAtivoId = $stmtStatus->fetchColumn();

        if (!$statusAtivoId) {
            throw new RuntimeException('Estado ativo para unidades não encontrado na base de dados.');
        }

        $stmtExistentes = $this->db->prepare("SELECT codigo, nome FROM unidades WHERE empresa_id = :empresa_id");
        $stmtExistentes->execute([':empresa_id' => $empresaId]);

        $codigosBD = [];
        $nomesBD = [];
        while ($row = $stmtExistentes->fetch(PDO::FETCH_ASSOC)) {
            $codigosBD[mb_strtolower(trim($row['codigo']))] = true;
            $nomesBD[mb_strtolower(trim($row['nome']))] = true;
        }

        $validas = [];
        $erros = [];
        $codigosNoArquivo = [];
        $nomesNoArquivo = [];

        foreach ($linhas as $indice => $linha) {
            if ($indice === 1) {
                continue; // Ignorar cabeçalho
            }

            $codigo = trim((string)($linha['A'] ?? ''));
            $nome = trim((string)($linha['B'] ?? ''));

            if ($codigo === '' && $nome === '') {
                continue;
            }

            $errosLinha = [];
            $casasDecimais = trim((string)($linha['C'] ?? '0'));
            $descricao = trim((string)($linha['D'] ?? '')) ?: null;

            if ($codigo === '') {
                $errosLinha[] = 'O código da unidade é obrigatório.';
            } elseif (mb_strlen($codigo) > 30) {
                $errosLinha[] = 'O código não pode exceder 30 caracteres.';
            }

            if ($nome === '') {
                $errosLinha[] = 'O nome da unidade é obrigatório.';
            } elseif (mb_strlen($nome) > 120) {
                $errosLinha[] = 'O nome não pode exceder 120 caracteres.';
            }

            if (!is_numeric($casasDecimais) || (int)$casasDecimais < 0 || (int)$casasDecimais > 6) {
                $errosLinha[] = 'As casas decimais devem ser um número inteiro entre 0 e 6.';
            }

            if ($codigo !== '') {
                $codigoLower = mb_strtolower($codigo);
                if (isset($codigosNoArquivo[$codigoLower])) {
                    $errosLinha[] = "O código '{$codigo}' está duplicado no próprio ficheiro.";
                } else {
                    $codigosNoArquivo[$codigoLower] = true;
                    if (isset($codigosBD[$codigoLower])) {
                        $errosLinha[] = "Já existe uma unidade registada com o código '{$codigo}'.";
                    }
                }
            }

            if ($nome !== '') {
                $nomeLower = mb_strtolower($nome);
                if (isset($nomesNoArquivo[$nomeLower])) {
                    $errosLinha[] = "O nome '{$nome}' está duplicado no próprio ficheiro.";
                } else {
                    $nomesNoArquivo[$nomeLower] = true;
                    if (isset($nomesBD[$nomeLower])) {
                        $errosLinha[] = "Já existe uma unidade registada com o nome '{$nome}'.";
                    }
                }
            }

            $dadosLinha = [
                'status_id' => (int)$statusAtivoId,
                'codigo' => mb_strtoupper($codigo),
                'nome' => $nome,
                'casas_decimais' => (int)$casasDecimais,
                'descricao' => $descricao
            ];

            if (empty($errosLinha)) {
                $validas[] = [
                    'linha' => $indice,
                    'dados' => $dadosLinha,
                    'preview' => [
                        'codigo' => mb_strtoupper($codigo),
                        'nome' => $nome,
                        'casas_decimais' => (int)$casasDecimais,
                        'descricao' => $descricao ?: '-'
                    ]
                ];
            } else {
                $erros[] = [
                    'linha' => $indice,
                    'dados' => ['codigo' => $codigo, 'nome' => $nome],
                    'erros' => $errosLinha
                ];
            }
        }

        $token = bin2hex(random_bytes(32));
        $_SESSION['importacao_unidades_' . $token] = $validas;

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
        $sessaoChave = 'importacao_unidades_' . $token;
        if (!isset($_SESSION[$sessaoChave]) || !is_array($_SESSION[$sessaoChave])) {
            throw new RuntimeException('A sessão de importação expirou ou é inválida.');
        }

        $validas = $_SESSION[$sessaoChave];
        unset($_SESSION[$sessaoChave]);

        if (empty($validas)) {
            throw new RuntimeException('Não existem registos válidos para importar.');
        }

        $unidadeModel = new Unidade($this->db);
        $criados = [];

        $this->db->beginTransaction();
        try {
            foreach ($validas as $item) {
                $dados = $item['dados'];
                $novoId = $unidadeModel->criar($empresaId, $dados);
                $criados[] = [
                    'id' => $novoId,
                    'codigo' => $dados['codigo'],
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
