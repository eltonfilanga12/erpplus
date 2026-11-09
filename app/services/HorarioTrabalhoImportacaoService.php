<?php

declare(strict_types=1);

final class HorarioTrabalhoImportacaoService
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
        $sheet->setTitle('Modelo Horários de Trabalho');

        $cabecalhos = [
            'Código',
            'Nome',
            'Descrição',
            'Hora Entrada (HH:MM)',
            'Hora Saída (HH:MM)',
            'Início Intervalo (HH:MM)',
            'Fim Intervalo (HH:MM)',
            'Tolerância Entrada (Minutos)',
            'Tolerância Saída (Minutos)',
            'Segunda-feira (Sim/Não)',
            'Terça-feira (Sim/Não)',
            'Quarta-feira (Sim/Não)',
            'Quinta-feira (Sim/Não)',
            'Sexta-feira (Sim/Não)',
            'Sábado (Sim/Não)',
            'Domingo (Sim/Não)',
            'Permite Horas Extras (Sim/Não)',
            'Permite Trabalho Noturno (Sim/Não)',
            'Horário Flexível (Sim/Não)'
        ];

        $coluna = 'A';
        foreach ($cabecalhos as $cabecalho) {
            $sheet->setCellValue($coluna . '1', $cabecalho);
            $sheet->getStyle($coluna . '1')->getFont()->setBold(true);
            $coluna++;
        }

        return $spreadsheet;
    }

    private function normalizarHoraExcel(mixed $valor): ?string
    {
        if ($valor === null || trim((string)$valor) === '') {
            return null;
        }
        $texto = trim((string)$valor);
        if (is_numeric($texto)) {
            $horas = floor((float)$texto * 24);
            $minutos = round(((float)$texto * 24 - $horas) * 60);
            return sprintf('%02d:%02d:00', $horas, $minutos);
        }
        if (preg_match('/^\d{2}:\d{2}$/', $texto)) {
            return $texto . ':00';
        }
        if (preg_match('/^\d{2}:\d{2}:\d{2}$/', $texto)) {
            return $texto;
        }
        return null;
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

        $stmtStatus = $this->db->prepare("SELECT id FROM status WHERE tipo = 'horario_trabalho' AND LOWER(nome) IN ('activo', 'ativo', 'activa', 'ativa') LIMIT 1");
        $stmtStatus->execute();
        $statusAtivoId = $stmtStatus->fetchColumn();

        if (!$statusAtivoId) {
            throw new RuntimeException('Estado ativo para horários de trabalho não encontrado na base de dados.');
        }

        $stmtHorario = $this->db->prepare("SELECT codigo, nome FROM horarios_trabalho WHERE empresa_id = :empresa_id");
        $stmtHorario->execute([':empresa_id' => $empresaId]);
        $horariosBDByCodigo = [];
        $horariosBDByNome = [];
        while ($row = $stmtHorario->fetch(PDO::FETCH_ASSOC)) {
            $horariosBDByCodigo[mb_strtolower(trim($row['codigo']))] = true;
            $horariosBDByNome[mb_strtolower(trim($row['nome']))] = true;
        }

        $validas = [];
        $erros = [];
        $codigosNoArquivo = [];
        $nomesNoArquivo = [];

        foreach ($linhas as $indice => $linha) {
            if ($indice === 1) {
                continue;
            }

            $codigo = trim((string)($linha['A'] ?? ''));
            $nome = trim((string)($linha['B'] ?? ''));

            if ($codigo === '' && $nome === '') {
                continue;
            }

            $errosLinha = [];
            $descricao = trim((string)($linha['C'] ?? '')) ?: null;
            $horaEntradaInput = trim((string)($linha['D'] ?? ''));
            $horaSaidaInput = trim((string)($linha['E'] ?? ''));
            $intervaloInicioInput = trim((string)($linha['F'] ?? '')) ?: null;
            $intervaloFimInput = trim((string)($linha['G'] ?? '')) ?: null;
            $tolEntradaInput = trim((string)($linha['H'] ?? '0'));
            $tolSaidaInput = trim((string)($linha['I'] ?? '0'));

            $segunda = in_array(mb_strtolower(trim((string)($linha['J'] ?? 'Sim'))), ['sim', '1', 'true', 'yes', 'on'], true) ? 1 : 0;
            $terca = in_array(mb_strtolower(trim((string)($linha['K'] ?? 'Sim'))), ['sim', '1', 'true', 'yes', 'on'], true) ? 1 : 0;
            $quarta = in_array(mb_strtolower(trim((string)($linha['L'] ?? 'Sim'))), ['sim', '1', 'true', 'yes', 'on'], true) ? 1 : 0;
            $quinta = in_array(mb_strtolower(trim((string)($linha['M'] ?? 'Sim'))), ['sim', '1', 'true', 'yes', 'on'], true) ? 1 : 0;
            $sexta = in_array(mb_strtolower(trim((string)($linha['N'] ?? 'Sim'))), ['sim', '1', 'true', 'yes', 'on'], true) ? 1 : 0;
            $sabado = in_array(mb_strtolower(trim((string)($linha['O'] ?? 'Não'))), ['sim', '1', 'true', 'yes', 'on'], true) ? 1 : 0;
            $domingo = in_array(mb_strtolower(trim((string)($linha['P'] ?? 'Não'))), ['sim', '1', 'true', 'yes', 'on'], true) ? 1 : 0;

            $permiteExtras = in_array(mb_strtolower(trim((string)($linha['Q'] ?? 'Sim'))), ['sim', '1', 'true', 'yes', 'on'], true) ? 1 : 0;
            $permiteNoturno = in_array(mb_strtolower(trim((string)($linha['R'] ?? 'Não'))), ['sim', '1', 'true', 'yes', 'on'], true) ? 1 : 0;
            $flexivel = in_array(mb_strtolower(trim((string)($linha['S'] ?? 'Não'))), ['sim', '1', 'true', 'yes', 'on'], true) ? 1 : 0;

            if ($codigo === '') {
                $errosLinha[] = 'O código do horário é obrigatório.';
            } elseif (mb_strlen($codigo) > 20) {
                $errosLinha[] = 'O código não pode exceder 20 caracteres.';
            }

            if ($nome === '') {
                $errosLinha[] = 'O nome do horário é obrigatório.';
            } elseif (mb_strlen($nome) > 120) {
                $errosLinha[] = 'O nome não pode exceder 120 caracteres.';
            }

            $codigoLower = mb_strtolower($codigo);
            if ($codigo !== '') {
                if (isset($codigosNoArquivo[$codigoLower])) {
                    $errosLinha[] = "O código '{$codigo}' está duplicado no próprio ficheiro.";
                } else {
                    $codigosNoArquivo[$codigoLower] = true;
                    if (isset($horariosBDByCodigo[$codigoLower])) {
                        $errosLinha[] = "Já existe um horário com o código '{$codigo}' na base de dados.";
                    }
                }
            }

            $nomeLower = mb_strtolower($nome);
            if ($nome !== '') {
                if (isset($nomesNoArquivo[$nomeLower])) {
                    $errosLinha[] = "O nome '{$nome}' está duplicado no próprio ficheiro.";
                } else {
                    $nomesNoArquivo[$nomeLower] = true;
                    if (isset($horariosBDByNome[$nomeLower])) {
                        $errosLinha[] = "Já existe um horário com o nome '{$nome}' na base de dados.";
                    }
                }
            }

            $horaEntrada = $this->normalizarHoraExcel($horaEntradaInput);
            $horaSaida = $this->normalizarHoraExcel($horaSaidaInput);

            if (!$horaEntrada) {
                $errosLinha[] = 'A hora de entrada é inválida ou obrigatória.';
            }
            if (!$horaSaida) {
                $errosLinha[] = 'A hora de saída é inválida ou obrigatória.';
            }

            if ($horaEntrada && $horaSaida && $horaEntrada === $horaSaida) {
                $errosLinha[] = 'A hora de entrada não pode ser igual à hora de saída.';
            }

            $intervaloInicio = $intervaloFim = null;
            if ($intervaloInicioInput !== null || $intervaloFimInput !== null) {
                if (($intervaloInicioInput === null) !== ($intervaloFimInput === null)) {
                    $errosLinha[] = 'Informe o início e o fim do intervalo em conjunto.';
                } else {
                    $intervaloInicio = $this->normalizarHoraExcel($intervaloInicioInput);
                    $intervaloFim = $this->normalizarHoraExcel($intervaloFimInput);
                    if ($intervaloInicio === $intervaloFim) {
                        $errosLinha[] = 'O início e o fim do intervalo não podem ser iguais.';
                    }
                }
            }

            $totalDias = $segunda + $terca + $quarta + $quinta + $sexta + $sabado + $domingo;
            if ($totalDias <= 0) {
                $errosLinha[] = 'Selecione pelo menos um dia de trabalho.';
            }

            $tolEntrada = is_numeric($tolEntradaInput) ? (int)$tolEntradaInput : 0;
            $tolSaida = is_numeric($tolSaidaInput) ? (int)$tolSaidaInput : 0;
            if ($tolEntrada > 1440 || $tolSaida > 1440 || $tolEntrada < 0 || $tolSaida < 0) {
                $errosLinha[] = 'Os valores de tolerância em minutos são inválidos.';
            }

            $cargaDiaria = 0;
            $cargaSemanal = 0;
            if ($horaEntrada && $horaSaida) {
                try {
                    $cargaDiaria = $this->calcularCargaDiaria($horaEntrada, $horaSaida, $intervaloInicio, $intervaloFim);
                    $cargaSemanal = round($cargaDiaria * $totalDias, 2);
                } catch (Throwable $e) {
                    $errosLinha[] = $e->getMessage();
                }
            }

            $atravessaMeiaNoite = $this->paraSegundos($horaSaida) <= $this->paraSegundos($horaEntrada);
            if ($atravessaMeiaNoite && $permiteNoturno !== 1) {
                $errosLinha[] = 'Horários que atravessam a meia-noite exigem a permissão de trabalho noturno.';
            }

            $dadosLinha = [
                'status_id' => (int)$statusAtivoId,
                'codigo' => mb_strtoupper($codigo),
                'nome' => $nome,
                'descricao' => $descricao,
                'hora_entrada' => $horaEntrada,
                'hora_saida' => $horaSaida,
                'intervalo_inicio' => $intervaloInicio,
                'intervalo_fim' => $intervaloFim,
                'tolerancia_entrada_minutos' => $tolEntrada,
                'tolerancia_saida_minutos' => $tolSaida,
                'carga_horaria_diaria' => $cargaDiaria,
                'carga_horaria_semanal' => $cargaSemanal,
                'segunda_feira' => $segunda,
                'terca_feira' => $terca,
                'quarta_feira' => $quarta,
                'quinta_feira' => $quinta,
                'sexta_feira' => $sexta,
                'sabado' => $sabado,
                'domingo' => $domingo,
                'permite_horas_extras' => $permiteExtras,
                'permite_trabalho_noturno' => $permiteNoturno,
                'horario_flexivel' => $flexivel
            ];

            if (empty($errosLinha)) {
                $validas[] = [
                    'linha' => $indice,
                    'dados' => $dadosLinha,
                    'preview' => [
                        'codigo' => mb_strtoupper($codigo),
                        'nome' => $nome,
                        'periodo' => substr($horaEntrada, 0, 5) . ' - ' . substr($horaSaida, 0, 5),
                        'carga_diaria' => number_format($cargaDiaria, 2, ',', '.') . 'h',
                        'carga_semanal' => number_format($cargaSemanal, 2, ',', '.') . 'h'
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
        $_SESSION['importacao_horarios_' . $token] = $validas;

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
        $sessaoChave = 'importacao_horarios_' . $token;
        if (!isset($_SESSION[$sessaoChave]) || !is_array($_SESSION[$sessaoChave])) {
            throw new RuntimeException('A sessão de importação expirou ou é inválida.');
        }

        $validas = $_SESSION[$sessaoChave];
        unset($_SESSION[$sessaoChave]);

        if (empty($validas)) {
            throw new RuntimeException('Não existem registos válidos para importar.');
        }

        $horarioModel = new HorarioTrabalho($this->db);
        $criados = [];

        $this->db->beginTransaction();
        try {
            foreach ($validas as $item) {
                $dados = $item['dados'];
                $novoId = $horarioModel->criar($empresaId, $usuarioId, $dados);
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
            'horarios' => $criados
        ];
    }

    private function normalioraHoraExcel(mixed $valor): ?string
    {
        if ($valor === null || trim((string)$valor) === '') {
            return null;
        }
        $texto = trim((string)$valor);
        if (is_numeric($texto)) {
            $horas = floor((float)$texto * 24);
            $minutos = round(((float)$texto * 24 - $horas) * 60);
            return sprintf('%02d:%02d:00', $horas, $minutos);
        }
        if (preg_match('/^\d{2}:\d{2}$/', $texto)) {
            return $texto . ':00';
        }
        if (preg_match('/^\d{2}:\d{2}:\d{2}$/', $texto)) {
            return $texto;
        }
        return null;
    }

    private function paraSegundos(string $hora): int
    {
        [$h, $m, $s] = array_map('intval', explode(':', $hora));
        return $h * 3600 + $m * 60 + $s;
    }

    private function calcularCargaDiaria(string $entrada, string $saida, ?string $intervaloInicio, ?string $intervaloFim): float
    {
        $e = $this->paraSegundos($entrada);
        $s = $this->paraSegundos($saida);
        if ($s <= $e) {
            $s += 86400;
        }
        $duracaoTrab = $s - $e;
        $duracaoIntervalo = 0;

        if ($intervaloInicio !== null && $intervaloFim !== null) {
            $ii = $this->paraSegundos($intervaloInicio);
            $if = $this->paraSegundos($intervaloFim);
            if ($ii < $e) {
                $ii += 86400;
            }
            if ($if <= $ii) {
                $if += 86400;
            }
            $duracaoIntervalo = $if - $ii;
        }

        $liq = $duracaoTrab - $duracaoIntervalo;
        if ($liq <= 0) {
            throw new RuntimeException('O intervalo excede ou iguala a jornada diária.');
        }
        return round($liq / 3600, 2);
    }
}
