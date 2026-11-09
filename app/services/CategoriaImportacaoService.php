<?php

declare(strict_types=1);

final class CategoriaImportacaoService
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
        $sheet->setTitle('Modelo Categorias');

        $cabecalhos = [
            'Nome',
            'Categoria Pai',
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

        $stmtStatus = $this->db->prepare("SELECT id FROM status WHERE tipo = 'categoria' AND LOWER(nome) IN ('activa', 'ativa') LIMIT 1");
        $stmtStatus->execute();
        $statusAtivoId = $stmtStatus->fetchColumn();

        if (!$statusAtivoId) {
            throw new RuntimeException('Estado ativo para categorias não encontrado na base de dados.');
        }

        $stmtExistentes = $this->db->prepare("SELECT id, nome FROM categorias WHERE empresa_id = :empresa_id");
        $stmtExistentes->execute([':empresa_id' => $empresaId]);
        $categoriasBD = [];
        while ($row = $stmtExistentes->fetch(PDO::FETCH_ASSOC)) {
            $categoriasBD[mb_strtolower(trim($row['nome']))] = (int)$row['id'];
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
            $categoriaPaiNome = trim((string)($linha['B'] ?? '')) ?: null;
            $descricao = trim((string)($linha['C'] ?? '')) ?: null;

            if (mb_strlen($nome) > 120) {
                $errosLinha[] = 'O nome não pode exceder 120 caracteres.';
            }

            $nomeLower = mb_strtolower($nome);
            if (isset($nomesNoArquivo[$nomeLower])) {
                $errosLinha[] = "O nome '{$nome}' está duplicado no próprio ficheiro.";
            } else {
                $nomesNoArquivo[$nomeLower] = true;
                if (isset($categoriasBD[$nomeLower])) {
                    $errosLinha[] = "Já existe uma categoria registada com o nome '{$nome}'.";
                }
            }

            if ($categoriaPaiNome !== null) {
                $paiLower = mb_strtolower($categoriaPaiNome);
                if (!isset($categoriasBD[$paiLower]) && !isset($nomesNoArquivo[$paiLower])) {
                    $errosLinha[] = "A categoria pai '{$categoriaPaiNome}' não foi encontrada.";
                }
            }

            $dadosLinha = [
                'status_id' => (int)$statusAtivoId,
                'categoria_pai_nome' => $categoriaPaiNome,
                'nome' => $nome,
                'descricao' => $descricao
            ];

            if (empty($errosLinha)) {
                $validas[] = [
                    'linha' => $indice,
                    'dados' => $dadosLinha,
                    'preview' => [
                        'nome' => $nome,
                        'categoria_pai_nome' => $categoriaPaiNome ?: 'Categoria principal',
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
        $_SESSION['importacao_categorias_' . $token] = $validas;

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
        $sessaoChave = 'importacao_categorias_' . $token;
        if (!isset($_SESSION[$sessaoChave]) || !is_array($_SESSION[$sessaoChave])) {
            throw new RuntimeException('A sessão de importação expirou ou é inválida.');
        }

        $validas = $_SESSION[$sessaoChave];
        unset($_SESSION[$sessaoChave]);

        if (empty($validas)) {
            throw new RuntimeException('Não existem registos válidos para importar.');
        }

        $categoriaModel = new Categoria($this->db);
        $criados = [];

        $this->db->beginTransaction();
        try {
            foreach ($validas as $item) {
                $dados = $item['dados'];
                $paiId = null;

                if (!empty($dados['categoria_pai_nome'])) {
                    $stmtPai = $this->db->prepare("SELECT id FROM categorias WHERE empresa_id = :empresa_id AND LOWER(nome) = LOWER(:nome) LIMIT 1");
                    $stmtPai->execute([':empresa_id' => $empresaId, ':nome' => $dados['categoria_pai_nome']]);
                    $paiId = $stmtPai->fetchColumn() ?: null;
                }

                $dadosInsercao = [
                    'status_id' => $dados['status_id'],
                    'categoria_pai_id' => $paiId ? (int)$paiId : null,
                    'nome' => $dados['nome'],
                    'descricao' => $dados['descricao']
                ];

                $novoId = $categoriaModel->criar($empresaId, $dadosInsercao);
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
