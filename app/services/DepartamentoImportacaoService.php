<?php

declare(strict_types=1);

final class DepartamentoImportacaoService
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
        $sheet->setTitle('Modelo Departamentos');

        $cabecalhos = [
            'Código',
            'Nome',
            'Filial (Código ou Nome)',
            'Departamento Pai (Código ou Nome)',
            'Centro de Custo (Código ou Nome)',
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

        $stmtStatus = $this->db->prepare("SELECT id FROM status WHERE tipo = 'departamento' AND LOWER(nome) IN ('activo', 'ativo', 'activa', 'ativa') LIMIT 1");
        $stmtStatus->execute();
        $statusAtivoId = $stmtStatus->fetchColumn();

        if (!$statusAtivoId) {
            throw new RuntimeException('Estado ativo para departamentos não encontrado na base de dados.');
        }

        // Mapear filiais da empresa (por ID, código e nome)
        $stmtFiliais = $this->db->prepare("SELECT id, codigo, nome FROM filiais WHERE empresa_id = :empresa_id");
        $stmtFiliais->execute([':empresa_id' => $empresaId]);
        $filiaisMap = [];
        while ($row = $stmtFiliais->fetch(PDO::FETCH_ASSOC)) {
            $filiaisMap[mb_strtolower(trim($row['codigo']))] = (int)$row['id'];
            $filiaisMap[mb_strtolower(trim($row['nome']))] = (int)$row['id'];
            $filiaisMap['id_' . $row['id']] = (int)$row['id'];
        }

        // Mapear centros de custo da empresa
        $stmtCentros = $this->db->prepare("SELECT id, codigo, nome FROM centros_custo WHERE empresa_id = :empresa_id");
        $stmtCentros->execute([':empresa_id' => $empresaId]);
        $centrosMap = [];
        while ($row = $stmtCentros->fetch(PDO::FETCH_ASSOC)) {
            $centrosMap[mb_strtolower(trim($row['codigo']))] = (int)$row['id'];
            $centrosMap[mb_strtolower(trim($row['nome']))] = (int)$row['id'];
            $centrosMap['id_' . $row['id']] = (int)$row['id'];
        }

        // Mapear departamentos existentes na BD
        $stmtDep = $this->db->prepare("SELECT id, codigo, nome FROM departamentos WHERE empresa_id = :empresa_id");
        $stmtDep->execute([':empresa_id' => $empresaId]);
        $departamentosBDByCodigo = [];
        $departamentosBDByNome = [];
        $departamentosBDMap = [];
        while ($row = $stmtDep->fetch(PDO::FETCH_ASSOC)) {
            $departamentosBDByCodigo[mb_strtolower(trim($row['codigo']))] = (int)$row['id'];
            $departamentosBDByNome[mb_strtolower(trim($row['nome']))] = (int)$row['id'];
            $departamentosBDMap[(int)$row['id']] = $row['nome'];
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
            $filialInput = trim((string)($linha['C'] ?? ''));
            $paiInput = trim((string)($linha['D'] ?? ''));
            $centroInput = trim((string)($linha['E'] ?? ''));
            $descricao = trim((string)($linha['F'] ?? '')) ?: null;

            if ($codigo === '') {
                $errosLinha[] = 'O código do departamento é obrigatório.';
            } elseif (mb_strlen($codigo) > 20) {
                $errosLinha[] = 'O código não pode exceder 20 caracteres.';
            }

            if ($nome === '') {
                $errosLinha[] = 'O nome do departamento é obrigatório.';
            } elseif (mb_strlen($nome) > 150) {
                $errosLinha[] = 'O nome não pode exceder 150 caracteres.';
            }

            $codigoLower = mb_strtolower($codigo);
            if ($codigo !== '') {
                if (isset($codigosNoArquivo[$codigoLower])) {
                    $errosLinha[] = "O código '{$codigo}' está duplicado no próprio ficheiro.";
                } else {
                    $codigosNoArquivo[$codigoLower] = $indice;
                    if (isset($departamentosBDByCodigo[$codigoLower])) {
                        $errosLinha[] = "Já existe um departamento com o código '{$codigo}' na base de dados.";
                    }
                }
            }

            $nomeLower = mb_strtolower($nome);
            if ($nome !== '') {
                if (isset($nomesNoArquivo[$nomeLower])) {
                    $errosLinha[] = "O nome '{$nome}' está duplicado no próprio ficheiro.";
                } else {
                    $nomesNoArquivo[$nomeLower] = $indice;
                    if (isset($departamentosBDByNome[$nomeLower])) {
                        $errosLinha[] = "Já existe um departamento com o nome '{$nome}' na base de dados.";
                    }
                }
            }

            // Validar Filial
            $filialId = null;
            if ($filialInput !== '') {
                $filialKey = mb_strtolower($filialInput);
                if (isset($filiaisMap[$filialKey])) {
                    $filialId = $filiaisMap[$filialKey];
                } else {
                    $errosLinha[] = "A filial '{$filialInput}' não foi encontrada.";
                }
            }

            // Validar Centro de Custo
            $centroId = null;
            if ($centroInput !== '') {
                $centroKey = mb_strtolower($centroInput);
                if (isset($centrosMap[$centroKey])) {
                    $centroId = $centrosMap[$centroKey];
                } else {
                    $errosLinha[] = "O centro de custo '{$centroInput}' não foi encontrado.";
                }
            }

            // Validar Departamento Pai (Pode ser da BD ou criado nas linhas anteriores do arquivo)
            $paiId = null;
            $paiNomeExibicao = 'Departamento principal';
            if ($paiInput !== '') {
                $paiKey = mb_strtolower($paiInput);
                if (isset($departamentosBDByCodigo[$paiKey])) {
                    $paiId = $departamentosBDByCodigo[$paiKey];
                    $paiNomeExibicao = $departamentosBDMap[$paiId] ?? $paiInput;
                } elseif (isset($departamentosBDByNome[$paiKey])) {
                    $paiId = $departamentosBDByNome[$paiKey];
                    $paiNomeExibicao = $paiInput;
                } elseif (isset($codigosNoArquivo[$paiKey])) {
                    $paiNomeExibicao = $paiInput; // Será resolvido no momento da inserção pelo código/nome criado
                } elseif (isset($nomesNoArquivo[$paiKey])) {
                    $paiNomeExibicao = $paiInput;
                } else {
                    $errosLinha[] = "O departamento superior '{$paiInput}' não foi encontrado.";
                }
            }

            $dadosLinha = [
                'status_id' => (int)$statusAtivoId,
                'filial_id' => $filialId,
                'departamento_pai_input' => $paiInput !== '' ? $paiInput : null,
                'centro_custo_id' => $centroId,
                'codigo' => mb_strtoupper($codigo),
                'nome' => $nome,
                'descricao' => $descricao
            ];

            if (empty($errosLinha)) {
                $validas[] = [
                    'linha' => $indice,
                    'dados' => $dadosLinha,
                    'preview' => [
                        'codigo' => mb_strtoupper($codigo),
                        'nome' => $nome,
                        'filial' => $filialInput !== '' ? $filialInput : 'Todas',
                        'departamento_pai' => $paiNomeExibicao,
                        'centro_custo' => $centroInput !== '' ? $centroInput : 'Não associado',
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
        $_SESSION['importacao_departamentos_' . $token] = $validas;

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
        $sessaoChave = 'importacao_departamentos_' . $token;
        if (!isset($_SESSION[$sessaoChave]) || !is_array($_SESSION[$sessaoChave])) {
            throw new RuntimeException('A sessão de importação expirou ou é inválida.');
        }

        $validas = $_SESSION[$sessaoChave];
        unset($_SESSION[$sessaoChave]);

        if (empty($validas)) {
            throw new RuntimeException('Não existem registos válidos para importar.');
        }

        $departamentoModel = new Departamento($this->db);
        $criados = [];

        $this->db->beginTransaction();
        try {
            foreach ($validas as $item) {
                $dados = $item['dados'];
                $paiInput = $dados['departamento_pai_input'];
                $paiId = null;

                if ($paiInput !== null) {
                    $paiKey = mb_strtolower($paiInput);
                    // Procurar na BD ou nos recém-criados
                    $stmtPai = $this->db->prepare("SELECT id FROM departamentos WHERE empresa_id = :empresa_id AND (LOWER(codigo) = :key1 OR LOWER(nome) = :key2) LIMIT 1");
                    $stmtPai->execute([':empresa_id' => $empresaId, ':key1' => $paiKey, ':key2' => $paiKey]);
                    $paiId = $stmtPai->fetchColumn() ?: null;

                    if (!$paiId) {
                        foreach ($criados as $criado) {
                            if (mb_strtolower($criado['codigo']) === $paiKey || mb_strtolower($criado['nome']) === $paiKey) {
                                $paiId = $criado['id'];
                                break;
                            }
                        }
                    }
                }

                $payload = [
                    'codigo' => $dados['codigo'],
                    'nome' => $dados['nome'],
                    'descricao' => $dados['descricao'],
                    'filial_id' => $dados['filial_id'],
                    'departamento_pai_id' => $paiId ? (int)$paiId : null,
                    'centro_custo_id' => $dados['centro_custo_id'],
                    'status_id' => $dados['status_id']
                ];

                $novoId = $departamentoModel->criar($empresaId, $usuarioId, $payload);
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
