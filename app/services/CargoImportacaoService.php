<?php

declare(strict_types=1);

final class CargoImportacaoService
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
        $sheet->setTitle('Modelo Cargos');

        $cabecalhos = [
            'Código',
            'Nome',
            'Departamento (Código ou Nome)',
            'Centro de Custo (Código ou Nome)',
            'Nível Hierárquico',
            'Salário Mínimo',
            'Salário Máximo',
            'Carga Horária Semanal',
            'Cargo de Chefia (Sim/Não)',
            'Permite Horas Extras (Sim/Não)',
            'Descrição',
            'Responsabilidades',
            'Requisitos'
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

        $stmtStatus = $this->db->prepare("SELECT id FROM status WHERE tipo = 'cargo' AND LOWER(nome) IN ('activo', 'ativo', 'activa', 'ativa') LIMIT 1");
        $stmtStatus->execute();
        $statusAtivoId = $stmtStatus->fetchColumn();

        if (!$statusAtivoId) {
            throw new RuntimeException('Estado ativo para cargos não encontrado na base de dados.');
        }

        // Mapear departamentos
        $stmtDep = $this->db->prepare("SELECT id, codigo, nome FROM departamentos WHERE empresa_id = :empresa_id");
        $stmtDep->execute([':empresa_id' => $empresaId]);
        $departamentosMap = [];
        while ($row = $stmtDep->fetch(PDO::FETCH_ASSOC)) {
            $departamentosMap[mb_strtolower(trim($row['codigo']))] = (int)$row['id'];
            $departamentosMap[mb_strtolower(trim($row['nome']))] = (int)$row['id'];
        }

        // Mapear centros de custo
        $stmtCentro = $this->db->prepare("SELECT id, codigo, nome FROM centros_custo WHERE empresa_id = :empresa_id");
        $stmtCentro->execute([':empresa_id' => $empresaId]);
        $centrosMap = [];
        while ($row = $stmtCentro->fetch(PDO::FETCH_ASSOC)) {
            $centrosMap[mb_strtolower(trim($row['codigo']))] = (int)$row['id'];
            $centrosMap[mb_strtolower(trim($row['nome']))] = (int)$row['id'];
        }

        // Mapear cargos existentes na BD
        $stmtCargo = $this->db->prepare("SELECT codigo, nome FROM cargos WHERE empresa_id = :empresa_id");
        $stmtCargo->execute([':empresa_id' => $empresaId]);
        $cargosBDByCodigo = [];
        $cargosBDByNome = [];
        while ($row = $stmtCargo->fetch(PDO::FETCH_ASSOC)) {
            $cargosBDByCodigo[mb_strtolower(trim($row['codigo']))] = true;
            $cargosBDByNome[mb_strtolower(trim($row['nome']))] = true;
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
            $depInput = trim((string)($linha['C'] ?? ''));
            $centroInput = trim((string)($linha['D'] ?? ''));
            $nivel = trim((string)($linha['E'] ?? '1'));
            $salarioMin = trim((string)($linha['F'] ?? ''));
            $salarioMax = trim((string)($linha['G'] ?? ''));
            $cargaHoraria = trim((string)($linha['H'] ?? '40'));
            $chefiaInput = trim((string)($linha['I'] ?? 'Não'));
            $horasExtrasInput = trim((string)($linha['J'] ?? 'Sim'));
            $descricao = trim((string)($linha['K'] ?? '')) ?: null;
            $responsabilidades = trim((string)($linha['L'] ?? '')) ?: null;
            $requisitos = trim((string)($linha['M'] ?? '')) ?: null;

            if ($codigo === '') {
                $errosLinha[] = 'O código do cargo é obrigatório.';
            } elseif (mb_strlen($codigo) > 20) {
                $errosLinha[] = 'O código não pode exceder 20 caracteres.';
            }

            if ($nome === '') {
                $errosLinha[] = 'O nome do cargo é obrigatório.';
            } elseif (mb_strlen($nome) > 150) {
                $errosLinha[] = 'O nome não pode exceder 150 caracteres.';
            }

            $codigoLower = mb_strtolower($codigo);
            if ($codigo !== '') {
                if (isset($codigosNoArquivo[$codigoLower])) {
                    $errosLinha[] = "O código '{$codigo}' está duplicado no próprio ficheiro.";
                } else {
                    $codigosNoArquivo[$codigoLower] = true;
                    if (isset($cargosBDByCodigo[$codigoLower])) {
                        $errosLinha[] = "Já existe um cargo com o código '{$codigo}' na base de dados.";
                    }
                }
            }

            $nomeLower = mb_strtolower($nome);
            if ($nome !== '') {
                if (isset($nomesNoArquivo[$nomeLower])) {
                    $errosLinha[] = "O nome '{$nome}' está duplicado no próprio ficheiro.";
                } else {
                    $nomesNoArquivo[$nomeLower] = true;
                    if (isset($cargosBDByNome[$nomeLower])) {
                        $errosLinha[] = "Já existe um cargo com o nome '{$nome}' na base de dados.";
                    }
                }
            }

            // Validar Departamento
            $depId = null;
            if ($depInput !== '') {
                $depKey = mb_strtolower($depInput);
                if (isset($departamentosMap[$depKey])) {
                    $depId = $departamentosMap[$depKey];
                } else {
                    $errosLinha[] = "O departamento '{$depInput}' não foi encontrado.";
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

            // Nível Hierárquico
            if (!is_numeric($nivel) || (int)$nivel <= 0 || (int)$nivel > 255) {
                $errosLinha[] = 'O nível hierárquico deve ser um número entre 1 e 255.';
            }

            // Salários
            $valMin = $salarioMin !== '' ? filter_var(str_replace(',', '.', $salarioMin), FILTER_VALIDATE_FLOAT) : null;
            $valMax = $salarioMax !== '' ? filter_var(str_replace(',', '.', $salarioMax), FILTER_VALIDATE_FLOAT) : null;

            if ($salarioMin !== '' && $valMin === false) {
                $errosLinha[] = 'O salário mínimo é inválido.';
            }
            if ($salarioMax !== '' && $valMax === false) {
                $errosLinha[] = 'O salário máximo é inválido.';
            }
            if ($valMin !== null && $valMax !== null && $valMax < $valMin) {
                $errosLinha[] = 'O salário máximo não pode ser inferior ao salário mínimo.';
            }

            // Carga Horária
            $valCarga = $cargaHoraria !== '' ? filter_var(str_replace(',', '.', $cargaHoraria), FILTER_VALIDATE_FLOAT) : 40.0;
            if ($valCarga === false || $valCarga <= 0 || $valCarga > 168) {
                $errosLinha[] = 'A carga horária semanal é inválida.';
            }

            $chefia = in_array(mb_strtolower($chefiaInput), ['sim', '1', 'true', 'yes', 'on'], true) ? 1 : 0;
            $horasExtras = in_array(mb_strtolower($horasExtrasInput), ['sim', '1', 'true', 'yes', 'on'], true) ? 1 : 0;

            $dadosLinha = [
                'status_id' => (int)$statusAtivoId,
                'departamento_id' => $depId,
                'centro_custo_id' => $centroId,
                'codigo' => mb_strtoupper($codigo),
                'nome' => $nome,
                'nivel_hierarquico' => (int)$nivel,
                'salario_minimo' => $valMin !== null ? round((float)$valMin, 2) : null,
                'salario_maximo' => $valMax !== null ? round((float)$valMax, 2) : null,
                'carga_horaria_semanal' => (float)$valCarga,
                'cargo_chefia' => $chefia,
                'permite_horas_extras' => $horasExtras,
                'descricao' => $descricao,
                'responsabilidades' => $responsabilidades,
                'requisitos' => $requisitos
            ];

            if (empty($errosLinha)) {
                $validas[] = [
                    'linha' => $indice,
                    'dados' => $dadosLinha,
                    'preview' => [
                        'codigo' => mb_strtoupper($codigo),
                        'nome' => $nome,
                        'departamento' => $depInput !== '' ? $depInput : 'Não associado',
                        'nivel' => (int)$nivel,
                        'salario' => ($valMin !== null || $valMax !== null) ? 'Definido' : 'Não definido',
                        'chefia' => $chefia ? 'Sim' : 'Não'
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
        $_SESSION['importacao_cargos_' . $token] = $validas;

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
        $sessaoChave = 'importacao_cargos_' . $token;
        if (!isset($_SESSION[$sessaoChave]) || !is_array($_SESSION[$sessaoChave])) {
            throw new RuntimeException('A sessão de importação expirou ou é inválida.');
        }

        $validas = $_SESSION[$sessaoChave];
        unset($_SESSION[$sessaoChave]);

        if (empty($validas)) {
            throw new RuntimeException('Não existem registos válidos para importar.');
        }

        $cargoModel = new Cargo($this->db);
        $criados = [];

        $this->db->beginTransaction();
        try {
            foreach ($validas as $item) {
                $dados = $item['dados'];
                $novoId = $cargoModel->criar($empresaId, $usuarioId, $dados);
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
