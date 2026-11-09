<?php

declare(strict_types=1);

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\IOFactory;

final class FuncionarioImportacaoService
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function gerarModeloExcel(int $empresaId): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Funcionarios');

        // Cabeçalhos do modelo
        $cabecalhos = [
            'Nº Funcionário*',
            'Primeiro Nome*',
            'Apelido*',
            'Sexo (M/F ou Outro)',
            'Data Nascimento (AAAA-MM-DD)',
            'Estado Civil',
            'Nacionalidade',
            'Naturalidade',
            'NUIT',
            'Nº BI',
            'Data Emissão BI (AAAA-MM-DD)',
            'Data Validade BI (AAAA-MM-DD)',
            'Entidade Emissora BI',
            'Nº INSS',
            'Nº Dependentes',
            'Residente Fiscal (1=Sim, 0=Não)',
            'Telefone Principal',
            'Telefone Alternativo',
            'E-mail Pessoal',
            'E-mail Profissional',
            'Endereço',
            'Província',
            'Distrito',
            'Cidade',
            'Bairro',
            'Filial (Nome)',
            'Departamento (Nome)*',
            'Cargo (Nome)*',
            'Horário de Trabalho',
            'Data Admissão (AAAA-MM-DD)*',
            'Tipo de Vínculo*',
            'Salário Base*',
            'Moeda',
            'Banco',
            'Nº Conta',
            'NIB',
            'Observações'
        ];

        $coluna = 'A';
        foreach ($cabecalhos as $cabecalho) {
            $sheet->setCellValue($coluna . '1', $cabecalho);
            $sheet->getStyle($coluna . '1')->getFont()->setBold(true);
            $coluna++;
        }

        // Exemplo de linha preenchida
        $exemplo = [
            'FUN-001',
            'João',
            'Silva',
            'M',
            '1990-05-15',
            'Solteiro',
            'Moçambicana',
            'Maputo',
            '123456789',
            '110100234567A',
            '2015-01-10',
            '2025-01-10',
            'DICASE',
            '987654321',
            '2',
            '1',
            '258821234567',
            '258841234568',
            'joao@email.com',
            'joao.silva@empresa.com',
            'Av. Julius Nyerere',
            'Maputo',
            'KaMpfumo',
            'Maputo',
            'Polana',
            '',
            'Recursos Humanos',
            'Assistente de RH',
            '',
            '2026-01-01',
            'CONTRATADO',
            '25000',
            'MZN',
            'BCI',
            '1234567890',
            '00011234567890123456',
            'Funcionário de exemplo'
        ];

        $coluna = 'A';
        foreach ($exemplo as $valor) {
            $sheet->setCellValue($coluna . '2', $valor);
            $coluna++;
        }

        return $spreadsheet;
    }

    public function validarArquivo(array $ficheiro, int $empresaId, int $usuarioId): array
    {
        if (!isset($ficheiro['tmp_name']) || !is_uploaded_file($ficheiro['tmp_name'])) {
            throw new RuntimeException('Ficheiro inválido ou não enviado.');
        }

        $spreadsheet = IOFactory::load($ficheiro['tmp_name']);
        $sheet = $spreadsheet->getActiveSheet();
        $linhas = $sheet->toArray(null, true, true, true);

        if (count($linhas) <= 1) {
            throw new RuntimeException('O ficheiro Excel está vazio ou contém apenas o cabeçalho.');
        }

        // Buscar departamentos e cargos válidos da empresa para validação estricta
        $departamentosValidos = $this->carregarDepartamentosEmpresa($empresaId);
        $cargosValidos = $this->carregarCargosEmpresa($empresaId);

        $validas = [];
        $erros = [];
        $totalLinhas = 0;

        // Ignorar a linha 1 (cabeçalhos)
        for ($i = 2; $i <= count($linhas); $i++) {
            $linha = $linhas[$i];

            // Verificar se a linha está totalmente vazia
            if (empty(array_filter($linha))) {
                continue;
            }

            $totalLinhas++;
            $errosLinha = [];

            // Obter automaticamente o ID do estado 'Activo' para funcionários (ID 73)
            $statusAtivoId = $this->obterIdStatusAtivoFuncionario();

            $dados = [
                'numero_funcionario' => trim((string)($linha['A'] ?? '')),
                'primeiro_nome' => trim((string)($linha['B'] ?? '')),
                'apelido' => trim((string)($linha['C'] ?? '')),
                'sexo' => strtoupper(trim((string)($linha['D'] ?? ''))),
                'data_nascimento' => trim((string)($linha['E'] ?? '')),
                'estado_civil' => trim((string)($linha['F'] ?? '')),
                'nacionalidade' => trim((string)($linha['G'] ?? 'Moçambicana')),
                'naturalidade' => trim((string)($linha['H'] ?? '')),
                'nuit' => trim((string)($linha['I'] ?? '')),
                'numero_bi' => trim((string)($linha['J'] ?? '')),
                'data_emissao_bi' => trim((string)($linha['K'] ?? '')),
                'data_validade_bi' => trim((string)($linha['L'] ?? '')),
                'entidade_emissora_bi' => trim((string)($linha['M'] ?? '')),
                'numero_inss' => trim((string)($linha['N'] ?? '')),
                'numero_dependentes' => (int)($linha['O'] ?? 0),
                'residente_fiscal' => (string)($linha['P'] ?? '1'),
                'telefone_principal' => trim((string)($linha['Q'] ?? '')),
                'telefone_alternativo' => trim((string)($linha['R'] ?? '')),
                'email_pessoal' => trim((string)($linha['S'] ?? '')),
                'email_profissional' => trim((string)($linha['T'] ?? '')),
                'endereco' => trim((string)($linha['U'] ?? '')),
                'provincia' => trim((string)($linha['V'] ?? '')),
                'distrito' => trim((string)($linha['W'] ?? '')),
                'cidade' => trim((string)($linha['X'] ?? '')),
                'bairro' => trim((string)($linha['Y'] ?? '')),
                'filial_nome' => trim((string)($linha['Z'] ?? '')),
                'departamento_nome' => trim((string)($linha['AA'] ?? '')),
                'cargo_nome' => trim((string)($linha['AB'] ?? '')),
                'horario_trabalho_id' => null,
                'data_admissao' => trim((string)($linha['AD'] ?? '')),
                'tipo_vinculo' => strtoupper(trim((string)($linha['AE'] ?? 'CONTRATADO'))),
                'salario_base' => (float)($linha['AF'] ?? 0),
                'moeda' => strtoupper(trim((string)($linha['AG'] ?? 'MZN'))),
                'banco_nome' => trim((string)($linha['AH'] ?? '')),
                'numero_conta_bancaria' => trim((string)($linha['AI'] ?? '')),
                'nib' => trim((string)($linha['AJ'] ?? '')),
                'observacoes' => trim((string)($linha['AK'] ?? '')),
                'status_id' => $statusAtivoId // Atribui sempre o ID do estado Activo (73)
            ];

            // Validações obrigatórias
            if ($dados['numero_funcionario'] === '') {
                $errosLinha[] = 'O número do funcionário é obrigatório.';
            }
            if ($dados['primeiro_nome'] === '') {
                $errosLinha[] = 'O primeiro nome é obrigatório.';
            }
            if ($dados['apelido'] === '') {
                $errosLinha[] = 'O apelido é obrigatório.';
            }
            if ($dados['data_admissao'] === '') {
                $errosLinha[] = 'A data de admissão é obrigatória.';
            }

            // Validação estricta de Departamento
            if ($dados['departamento_nome'] === '') {
                $errosLinha[] = 'O departamento é obrigatório.';
            } else {
                $deptNormalizado = mb_strtolower($dados['departamento_nome']);
                if (isset($departamentosValidos[$deptNormalizado])) {
                    $dados['departamento_id'] = $departamentosValidos[$deptNormalizado]['id'];
                } else {
                    $errosLinha[] = "O departamento '{$dados['departamento_nome']}' não está cadastrado no sistema.";
                }
            }

            // Validação estricta de Cargo
            if ($dados['cargo_nome'] === '') {
                $errosLinha[] = 'O cargo é obrigatório.';
            } else {
                $cargoNormalizado = mb_strtolower($dados['cargo_nome']);
                if (isset($cargosValidos[$cargoNormalizado])) {
                    $dados['cargo_id'] = $cargosValidos[$cargoNormalizado]['id'];
                } else {
                    $errosLinha[] = "O cargo '{$dados['cargo_nome']}' não está cadastrado no sistema.";
                }
            }

            if (count($errosLinha) > 0) {
                $erros[] = [
                    'linha' => $i,
                    'sku' => $dados['numero_funcionario'] ?: '-',
                    'nome' => trim($dados['primeiro_nome'] . ' ' . $dados['apelido']) ?: '-',
                    'erro' => $errosLinha
                ];
            } else {
                $validas[] = [
                    'linha' => $i,
                    'dados' => $dados,
                    'preview' => [
                        'numero_funcionario' => $dados['numero_funcionario'],
                        'nome_completo' => trim($dados['primeiro_nome'] . ' ' . $dados['apelido']),
                        'sexo' => $dados['sexo'] ?: '-',
                        'filial_nome' => $dados['filial_nome'] ?: 'Geral',
                        'departamento_nome' => $dados['departamento_nome'],
                        'cargo_nome' => $dados['cargo_nome'],
                        'nuit' => $dados['nuit'] ?: '-',
                        'numero_bi' => $dados['numero_bi'] ?: '-',
                        'telefone' => $dados['telefone_principal'] ?: '-',
                        'email' => $dados['email_profissional'] ?: '-',
                        'data_admissao' => $dados['data_admissao'],
                        'salario_base' => number_format($dados['salario_base'], 2, '.', ','),
                        'tipo_vinculo' => $dados['tipo_vinculo']
                    ]
                ];
            }
        }

        $token = bin2hex(random_bytes(32));
        $_SESSION['importacao_funcionarios_' . $token] = [
            'validas' => $validas,
            'empresa_id' => $empresaId
        ];

        return [
            'token' => $token,
            'total_linhas' => $totalLinhas,
            'total_validas' => count($validas),
            'total_erros' => count($erros),
            'validas' => $validas,
            'erros' => $erros
        ];
    }

    public function confirmar(string $token, int $empresaId, int $usuarioId): array
    {
        $chaveSessao = 'importacao_funcionarios_' . $token;

        if (!isset($_SESSION[$chaveSessao])) {
            throw new RuntimeException('A sessão de importação expirou ou é inválida.');
        }

        $sessao = $_SESSION[$chaveSessao];
        if ((int)$sessao['empresa_id'] !== $empresaId) {
            throw new RuntimeException('Erro de permissão na importação.');
        }

        $validas = $sessao['validas'] ?? [];
        if (count($validas) === 0) {
            throw new RuntimeException('Não existem funcionários válidos para importar.');
        }

        $model = new Funcionario($this->db);
        $produtosCriados = [];

        $this->db->beginTransaction();

        try {
            foreach ($validas as $item) {
                $dados = $item['dados'];

                // Criar o funcionário
                $novoId = $model->criar($empresaId, $usuarioId, $dados);

                // Inserir dados fiscais caso necessário
                if ($novoId > 0 && (!empty($dados['nuit']) || $dados['numero_dependentes'] > 0)) {
                    $stmtFiscal = $this->db->prepare("
                        INSERT INTO funcionario_dados_fiscais 
                        (funcionario_id, numero_dependentes, residente_fiscal, nuit, criado_em, atualizado_em)
                        VALUES (:func_id, :dependentes, :residente, :nuit, NOW(), NOW())
                        ON DUPLICATE KEY UPDATE
                        numero_dependentes = VALUES(numero_dependentes),
                        residente_fiscal = VALUES(residente_fiscal),
                        nuit = VALUES(nuit),
                        atualizado_em = NOW()
                    ");

                    $stmtFiscal->execute([
                        ':func_id' => $novoId,
                        ':dependentes' => $dados['numero_dependentes'] ?? 0,
                        ':residente' => $dados['residente_fiscal'] ?? 1,
                        ':nuit' => $dados['nuit'] ?? null
                    ]);
                }

                // Inserir dados de salário se houver tabela específica ou modelo
                // ...

                $produtosCriados[] = [
                    'id' => $novoId,
                    'sku' => $dados['numero_funcionario'],
                    'nome' => trim($dados['primeiro_nome'] . ' ' . $dados['apelido']),
                    'linha' => $item['linha']
                ];
            }

            $this->db->commit();
            unset($_SESSION[$chaveSessao]);

            return [
                'produtos' => $produtosCriados,
                'mensagem' => count($produtosCriados) . ' funcionário(s) importado(s) com sucesso.'
            ];
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    private function carregarDepartamentosEmpresa(int $empresaId): array
    {
        $stmt = $this->db->prepare("SELECT id, nome FROM departamentos WHERE empresa_id = :empresa_id");
        $stmt->execute([':empresa_id' => $empresaId]);
        $departamentos = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $departamentos[mb_strtolower(trim($row['nome']))] = $row;
        }
        return $departamentos;
    }

    private function obterIdStatusAtivoFuncionario(): int
    {
        $stmt = $this->db->prepare("
            SELECT id FROM status 
            WHERE tipo = 'funcionario' 
              AND (LOWER(nome) = 'activo' OR LOWER(nome) = 'ativo') 
            LIMIT 1
        ");
        $stmt->execute();
        $id = $stmt->fetchColumn();

        // Se por algum motivo não encontrar, retorna 73 diretamente conforme a tabela de status
        return $id ? (int)$id : 73;
    }

    private function carregarCargosEmpresa(int $empresaId): array
    {
        $stmt = $this->db->prepare("SELECT id, nome FROM cargos WHERE empresa_id = :empresa_id");
        $stmt->execute([':empresa_id' => $empresaId]);
        $cargos = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $cargos[mb_strtolower(trim($row['nome']))] = $row;
        }
        return $cargos;
    }
}
