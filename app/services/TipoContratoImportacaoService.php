<?php

declare(strict_types=1);

final class TipoContratoImportacaoService
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
        $sheet->setTitle('Modelo Tipos de Contrato');

        $cabecalhos = [
            'Código',
            'Nome',
            'Descrição',
            'Duração Determinada (Sim/Não)',
            'Duração (Meses)',
            'Período Experimental (Dias)',
            'Renovação Automática (Sim/Não)',
            'Número Máximo de Renovações',
            'Exigir Data Final (Sim/Não)',
            'Permite Tempo Parcial (Sim/Não)',
            'Permite Horas Extras (Sim/Não)',
            'Gera Férias (Sim/Não)',
            'Gera Subsídio de Férias (Sim/Não)',
            'Gera Subsídio de Natal (Sim/Não)',
            'Sujeito a INSS (Sim/Não)',
            'Sujeito a IRPS (Sim/Não)',
            'Aviso Prévio (Dias)'
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

        $stmtStatus = $this->db->prepare("SELECT id FROM status WHERE tipo = 'tipo_contrato' AND LOWER(nome) IN ('activo', 'ativo', 'activa', 'ativa') LIMIT 1");
        $stmtStatus->execute();
        $statusAtivoId = $stmtStatus->fetchColumn();

        if (!$statusAtivoId) {
            throw new RuntimeException('Estado ativo para tipos de contrato não encontrado na base de dados.');
        }

        // Mapear tipos de contrato existentes na BD para evitar duplicados
        $stmtTipo = $this->db->prepare("SELECT codigo, nome FROM tipos_contratos WHERE empresa_id = :empresa_id");
        $stmtTipo->execute([':empresa_id' => $empresaId]);
        $tiposBDByCodigo = [];
        $tiposBDByNome = [];
        while ($row = $stmtTipo->fetch(PDO::FETCH_ASSOC)) {
            $tiposBDByCodigo[mb_strtolower(trim($row['codigo']))] = true;
            $tiposBDByNome[mb_strtolower(trim($row['nome']))] = true;
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
            $descricao = trim((string)($linha['C'] ?? '')) ?: null;
            $duracaoDeterminadaInput = trim((string)($linha['D'] ?? 'Não'));
            $duracaoMesesInput = trim((string)($linha['E'] ?? ''));
            $periodoExpInput = trim((string)($linha['F'] ?? ''));
            $renovacaoAutoInput = trim((string)($linha['G'] ?? 'Não'));
            $maxRenovInput = trim((string)($linha['H'] ?? ''));
            $exigeDataFimInput = trim((string)($linha['I'] ?? ''));
            $tempoParcialInput = trim((string)($linha['J'] ?? 'Sim'));
            $horasExtrasInput = trim((string)($linha['K'] ?? 'Sim'));
            $geraFeriasInput = trim((string)($linha['L'] ?? 'Sim'));
            $subsFeriasInput = trim((string)($linha['M'] ?? 'Sim'));
            $subsNatalInput = trim((string)($linha['N'] ?? 'Sim'));
            $sujeitoInssInput = trim((string)($linha['O'] ?? 'Sim'));
            $sujeitoIrpsInput = trim((string)($linha['P'] ?? 'Sim'));
            $avisoPrevioInput = trim((string)($linha['Q'] ?? ''));

            if ($codigo === '') {
                $errosLinha[] = 'O código do tipo de contrato é obrigatório.';
            } elseif (mb_strlen($codigo) > 20) {
                $errosLinha[] = 'O código não pode exceder 20 caracteres.';
            }

            if ($nome === '') {
                $errosLinha[] = 'O nome do tipo de contrato é obrigatório.';
            } elseif (mb_strlen($nome) > 120) {
                $errosLinha[] = 'O nome não pode exceder 120 caracteres.';
            }

            $codigoLower = mb_strtolower($codigo);
            if ($codigo !== '') {
                if (isset($codigosNoArquivo[$codigoLower])) {
                    $errosLinha[] = "O código '{$codigo}' está duplicado no próprio ficheiro.";
                } else {
                    $codigosNoArquivo[$codigoLower] = true;
                    if (isset($tiposBDByCodigo[$codigoLower])) {
                        $errosLinha[] = "Já existe um tipo de contrato com o código '{$codigo}' na base de dados.";
                    }
                }
            }

            $nomeLower = mb_strtolower($nome);
            if ($nome !== '') {
                if (isset($nomesNoArquivo[$nomeLower])) {
                    $errosLinha[] = "O nome '{$nome}' está duplicado no próprio ficheiro.";
                } else {
                    $nomesNoArquivo[$nomeLower] = true;
                    if (isset($tiposBDByNome[$nomeLower])) {
                        $errosLinha[] = "Já existe um tipo de contrato com o nome '{$nome}' na base de dados.";
                    }
                }
            }

            $duracaoDeterminada = in_array(mb_strtolower($duracaoDeterminadaInput), ['sim', '1', 'true', 'yes', 'on'], true) ? 1 : 0;

            $duracaoMeses = null;
            if ($duracaoDeterminada === 1) {
                if ($duracaoMesesInput === '' || !is_numeric($duracaoMesesInput) || (int)$duracaoMesesInput <= 0) {
                    $errosLinha[] = 'A duração em meses é obrigatória e deve ser superior a 0 para contratos de duração determinada.';
                } else {
                    $duracaoMeses = (int)$duracaoMesesInput;
                }
            }

            $renovacaoAuto = in_array(mb_strtolower($renovacaoAutoInput), ['sim', '1', 'true', 'yes', 'on'], true) ? 1 : 0;
            if ($renovacaoAuto === 1 && $duracaoDeterminada !== 1) {
                $errosLinha[] = 'A renovação automática só pode ser aplicada a contratos de duração determinada.';
            }

            $maxRenovacoes = null;
            if ($renovacaoAuto === 1) {
                if ($maxRenovInput === '' || !is_numeric($maxRenovInput) || (int)$maxRenovInput < 0) {
                    $errosLinha[] = 'Informe o número máximo de renovações válidas.';
                } else {
                    $maxRenovacoes = (int)$maxRenovInput;
                }
            }

            $exigeDataFim = $duracaoDeterminada === 1 ? 1 : (in_array(mb_strtolower($exigeDataFimInput), ['sim', '1', 'true', 'yes', 'on'], true) ? 1 : 0);

            $periodoExp = null;
            if ($periodoExpInput !== '') {
                if (!is_numeric($periodoExpInput) || (int)$periodoExpInput < 0 || (int)$periodoExpInput > 3650) {
                    $errosLinha[] = 'O período experimental em dias é inválido.';
                } else {
                    $periodoExp = (int)$periodoExpInput;
                }
            }

            $avisoPrevio = null;
            if ($avisoPrevioInput !== '') {
                if (!is_numeric($avisoPrevioInput) || (int)$avisoPrevioInput < 0 || (int)$avisoPrevioInput > 3650) {
                    $errosLinha[] = 'O aviso prévio em dias é inválido.';
                } else {
                    $avisoPrevio = (int)$avisoPrevioInput;
                }
            }

            $dadosLinha = [
                'status_id' => (int)$statusAtivoId,
                'codigo' => mb_strtoupper($codigo),
                'nome' => $nome,
                'descricao' => $descricao,
                'duracao_determinada' => $duracaoDeterminada,
                'duracao_meses' => $duracaoMeses,
                'periodo_experimental_dias' => $periodoExp,
                'renovacao_automatica' => $renovacaoAuto,
                'numero_maximo_renovacoes' => $maxRenovacoes,
                'exige_data_fim' => $exigeDataFim,
                'permite_tempo_parcial' => in_array(mb_strtolower($tempoParcialInput), ['sim', '1', 'true', 'yes', 'on'], true) ? 1 : 0,
                'permite_horas_extras' => in_array(mb_strtolower($horasExtrasInput), ['sim', '1', 'true', 'yes', 'on'], true) ? 1 : 0,
                'gera_ferias' => in_array(mb_strtolower($geraFeriasInput), ['sim', '1', 'true', 'yes', 'on'], true) ? 1 : 0,
                'gera_subsidio_ferias' => in_array(mb_strtolower($subsFeriasInput), ['sim', '1', 'true', 'yes', 'on'], true) ? 1 : 0,
                'gera_subsidio_natal' => in_array(mb_strtolower($subsNatalInput), ['sim', '1', 'true', 'yes', 'on'], true) ? 1 : 0,
                'sujeito_inss' => in_array(mb_strtolower($sujeitoInssInput), ['sim', '1', 'true', 'yes', 'on'], true) ? 1 : 0,
                'sujeito_irps' => in_array(mb_strtolower($sujeitoIrpsInput), ['sim', '1', 'true', 'yes', 'on'], true) ? 1 : 0,
                'aviso_previo_dias' => $avisoPrevio
            ];

            if (empty($errosLinha)) {
                $validas[] = [
                    'linha' => $indice,
                    'dados' => $dadosLinha,
                    'preview' => [
                        'codigo' => mb_strtoupper($codigo),
                        'nome' => $nome,
                        'duracao' => $duracaoDeterminada ? ($duracaoMeses . ' meses') : 'Indeterminada',
                        'renovacao' => $renovacaoAuto ? 'Sim' : 'Não',
                        'inss' => $dadosLinha['sujeito_inss'] ? 'Sim' : 'Não'
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
        $_SESSION['importacao_tipos_contratos_' . $token] = $validas;

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
        $sessaoChave = 'importacao_tipos_contratos_' . $token;
        if (!isset($_SESSION[$sessaoChave]) || !is_array($_SESSION[$sessaoChave])) {
            throw new RuntimeException('A sessão de importação expirou ou é inválida.');
        }

        $validas = $_SESSION[$sessaoChave];
        unset($_SESSION[$sessaoChave]);

        if (empty($validas)) {
            throw new RuntimeException('Não existem registos válidos para importar.');
        }

        $tipoModel = new TipoContrato($this->db);
        $criados = [];

        $this->db->beginTransaction();
        try {
            foreach ($validas as $item) {
                $dados = $item['dados'];
                $novoId = $tipoModel->criar($empresaId, $usuarioId, $dados);
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
