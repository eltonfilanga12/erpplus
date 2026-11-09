<?php

declare(strict_types=1);

final class ClienteImportacaoService
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
        $sheet->setTitle('Modelo Clientes');

        // Cabeçalhos limpos e organizados
        $cabecalhos = [
            'Nome',
            'Tipo (PARTICULAR/EMPRESA)',
            'Nome Comercial',
            'NUIT',
            'Email',
            'Telefone',
            'Contacto',
            'Endereço',
            'Bairro',
            'Cidade',
            'Província',
            'País',
            'Limite de Crédito',
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

        // Descobrir IDs de status Ativo padrão
        $stmtStatus = $this->db->prepare("SELECT id FROM status WHERE tipo = 'cliente' AND LOWER(nome) IN ('activo', 'ativo') LIMIT 1");
        $stmtStatus->execute();
        $statusAtivoId = $stmtStatus->fetchColumn();

        if (!$statusAtivoId) {
            throw new RuntimeException('Estado ativo para clientes não encontrado na base de dados.');
        }

        $validas = [];
        $erros = [];
        $nuitsNoArquivo = [];
        $emailsNoArquivo = [];

        // Consultas para verificar duplicidade na BD
        $stmtNuit = $this->db->prepare("SELECT COUNT(*) FROM clientes WHERE empresa_id = :empresa_id AND nuit = :nuit");
        $stmtEmail = $this->db->prepare("SELECT COUNT(*) FROM clientes WHERE empresa_id = :empresa_id AND LOWER(email) = LOWER(:email)");

        foreach ($linhas as $indice => $linha) {
            if ($indice === 1) {
                continue; // Ignorar cabeçalho
            }

            $nome = trim((string)($linha['A'] ?? ''));
            if ($nome === '') {
                continue; // Linha vazia
            }

            $errosLinha = [];

            $tipo = strtoupper(trim((string)($linha['B'] ?? 'PARTICULAR')));
            if (!in_array($tipo, ['PARTICULAR', 'EMPRESA'], true)) {
                $tipo = 'PARTICULAR';
            }

            $nomeComercial = trim((string)($linha['C'] ?? '')) ?: null;
            $nuit = trim((string)($linha['D'] ?? '')) ?: null;
            $email = trim((string)($linha['E'] ?? '')) ?: null;
            $telefone = trim((string)($linha['F'] ?? '')) ?: null;
            $contacto = trim((string)($linha['G'] ?? '')) ?: null;
            $endereco = trim((string)($linha['H'] ?? '')) ?: null;
            $bairro = trim((string)($linha['I'] ?? '')) ?: null;
            $cidade = trim((string)($linha['J'] ?? '')) ?: null;
            $provincia = trim((string)($linha['K'] ?? '')) ?: null;
            $pais = trim((string)($linha['L'] ?? '')) ?: 'Moçambique';

            $limiteCredito = 0.00;
            $limiteBruto = str_replace(',', '.', trim((string)($linha['M'] ?? '0')));
            if ($limiteBruto !== '' && is_numeric($limiteBruto)) {
                $limiteCredito = max(0.00, (float)$limiteBruto);
            }

            $observacoes = trim((string)($linha['N'] ?? '')) ?: null;

            // Validações de obrigatoriedade e duplicados
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
                        $errosLinha[] = "Já existe um cliente registado com o NUIT '{$nuit}'.";
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
                        $errosLinha[] = "Já existe um cliente registado com o email '{$email}'.";
                    }
                }
            }

            $dadosLinha = [
                'status_id' => (int)$statusAtivoId,
                'tipo' => $tipo,
                'nome' => $nome,
                'nome_comercial' => $nomeComercial,
                'nuit' => $nuit,
                'email' => $email ? mb_strtolower($email) : null,
                'telefone' => $telefone,
                'contacto' => $contacto,
                'endereco' => $endereco,
                'bairro' => $bairro,
                'cidade' => $cidade,
                'provincia' => $provincia,
                'pais' => $pais,
                'limite_credito' => number_format($limiteCredito, 2, '.', ''),
                'observacoes' => $observacoes
            ];

            if (empty($errosLinha)) {
                $validas[] = [
                    'linha' => $indice,
                    'dados' => $dadosLinha,
                    'preview' => [
                        'nome' => $nome,
                        'tipo' => $tipo,
                        'nuit' => $nuit ?: '-',
                        'telefone' => $telefone ?: '-',
                        'email' => $email ?: '-'
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
        $_SESSION['importacao_clientes_' . $token] = $validas;

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
        $sessaoChave = 'importacao_clientes_' . $token;
        if (!isset($_SESSION[$sessaoChave]) || !is_array($_SESSION[$sessaoChave])) {
            throw new RuntimeException('A sessão de importação expirou ou é inválida.');
        }

        $validas = $_SESSION[$sessaoChave];
        unset($_SESSION[$sessaoChave]);

        if (empty($validas)) {
            throw new RuntimeException('Não existem registos válidos para importar.');
        }

        $clienteModel = new Cliente($this->db);
        $criados = [];

        $this->db->beginTransaction();
        try {
            foreach ($validas as $item) {
                $dados = $item['dados'];
                $novoId = $clienteModel->criar($empresaId, $dados);
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
            'produtos' => $criados // Mantido com chave compatível para logs/auditoria
        ];
    }
}
