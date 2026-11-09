<?php

declare(strict_types=1);

final class FornecedorImportacaoService
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
        $sheet->setTitle('Modelo Fornecedores');

        $cabecalhos = [
            'Nome',
            'Nome Comercial',
            'NUIT',
            'Email',
            'Telefone',
            'Endereço',
            'Cidade',
            'País',
            'Pessoa de Contacto',
            'Observações'
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

        // Obter ID do status ativo/padrão para fornecedores
        $stmtStatus = $this->db->prepare("SELECT id FROM status WHERE tipo = 'fornecedor' AND LOWER(nome) IN ('activo', 'ativo') LIMIT 1");
        $stmtStatus->execute();
        $statusAtivoId = $stmtStatus->fetchColumn();

        if (!$statusAtivoId) {
            throw new RuntimeException('Estado ativo para fornecedores não encontrado na base de dados.');
        }

        $validas = [];
        $erros = [];
        $nuitsNoArquivo = [];
        $emailsNoArquivo = [];

        $stmtNuit = $this->db->prepare("SELECT COUNT(*) FROM fornecedores WHERE empresa_id = :empresa_id AND nuit = :nuit");
        $stmtEmail = $this->db->prepare("SELECT COUNT(*) FROM fornecedores WHERE empresa_id = :empresa_id AND LOWER(email) = LOWER(:email)");

        foreach ($linhas as $indice => $linha) {
            if ($indice === 1) {
                continue; // Ignorar cabeçalho
            }

            $nome = trim((string)($linha['A'] ?? ''));
            if ($nome === '') {
                continue;
            }

            $errosLinha = [];

            $nomeComercial = trim((string)($linha['B'] ?? '')) ?: null;
            $nuit = trim((string)($linha['C'] ?? '')) ?: null;
            $email = trim((string)($linha['D'] ?? '')) ?: null;
            $telefone = trim((string)($linha['E'] ?? '')) ?: null;
            $endereco = trim((string)($linha['F'] ?? '')) ?: null;
            $cidade = trim((string)($linha['G'] ?? '')) ?: null;
            $pais = trim((string)($linha['H'] ?? '')) ?: 'Moçambique';
            $contacto = trim((string)($linha['I'] ?? '')) ?: null;
            $observacoes = trim((string)($linha['J'] ?? '')) ?: null;

            if (mb_strlen($nome) > 150) {
                $errosLinha[] = 'O nome não pode exceder 150 caracteres.';
            }

            if ($nuit !== null) {
                if (isset($nuitsNoArquivo[$nuit])) {
                    $errosLinha[] = "O NUIT '{$nuit}' está duplicado no próprio ficheiro.";
                } else {
                    $nuitsNoArquivo[$nuit] = true;
                    $stmtNuit->execute([':empresa_id' => $empresaId, ':nuit' => $nuit]);
                    if ($stmtNuit->fetchColumn() > 0) {
                        $errosLinha[] = "Já existe um fornecedor registado com o NUIT '{$nuit}'.";
                    }
                }
            }

            if ($email !== null) {
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $errosLinha[] = "O email '{$email}' é inválido.";
                } elseif (isset($emailsNoArquivo[strtolower($email)])) {
                    $errosLinha[] = "O email '{$email}' está duplicado no próprio ficheiro.";
                } else {
                    $emailsNoArquivo[strtolower($email)] = true;
                    $stmtEmail->execute([':empresa_id' => $empresaId, ':email' => $email]);
                    if ($stmtEmail->fetchColumn() > 0) {
                        $errosLinha[] = "Já existe um fornecedor registado com o email '{$email}'.";
                    }
                }
            }

            $dadosLinha = [
                'status_id' => (int)$statusAtivoId,
                'nome' => $nome,
                'nome_comercial' => $nomeComercial,
                'nuit' => $nuit,
                'email' => $email ? mb_strtolower($email) : null,
                'telefone' => $telefone,
                'endereco' => $endereco,
                'cidade' => $cidade,
                'pais' => $pais,
                'contacto' => $contacto,
                'observacoes' => $observacoes
            ];

            if (empty($errosLinha)) {
                $validas[] = [
                    'linha' => $indice,
                    'dados' => $dadosLinha,
                    'preview' => [
                        'nome' => $nome,
                        'nuit' => $nuit ?: '-',
                        'telefone' => $telefone ?: '-',
                        'email' => $email ?: '-',
                        'cidade' => $cidade ?: '-'
                    ]
                ];
            } else {
                $erros[] = [
                    'linha' => $indice,
                    'dados' => ['nome' => $nome, 'nuit' => $nuit],
                    'erros' => $errosLinha
                ];
            }
        }

        $token = bin2hex(random_bytes(32));
        $_SESSION['importacao_fornecedores_' . $token] = $validas;

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
        $sessaoChave = 'importacao_fornecedores_' . $token;
        if (!isset($_SESSION[$sessaoChave]) || !is_array($_SESSION[$sessaoChave])) {
            throw new RuntimeException('A sessão de importação expirou ou é inválida.');
        }

        $validas = $_SESSION[$sessaoChave];
        unset($_SESSION[$sessaoChave]);

        if (empty($validas)) {
            throw new RuntimeException('Não existem registos válidos para importar.');
        }

        $fornecedorModel = new Fornecedor($this->db);
        $criados = [];

        $this->db->beginTransaction();
        try {
            foreach ($validas as $item) {
                $dados = $item['dados'];
                $novoId = $fornecedorModel->criar($empresaId, $dados);
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
            'produtos' => $criados // Chave unificada para logs
        ];
    }
}
