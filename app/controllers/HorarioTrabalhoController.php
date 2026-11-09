<?php

declare(strict_types=1);

require_once __DIR__ . '/../services/HorarioTrabalhoImportacaoService.php';
require_once __DIR__
    . '/../models/HorarioTrabalho.php';

final class HorarioTrabalhoController
{
    private HorarioTrabalho $model;

    public function __construct(PDO $pdo)
    {
        $this->model =
            new HorarioTrabalho(
                $pdo
            );
    }

    // ==================================================
    // DADOS INICIAIS
    // ==================================================

    public function dadosIniciais(): array
    {
        return $this->model
            ->dadosIniciais(
                $this->empresaId()
            );
    }


// Adicionar dentro da classe HorarioTrabalhoController:

public function getPdo(): PDO
{
    $reflection = new ReflectionClass($this->model);
    $property = $reflection->getProperty('pdo');
    $property->setAccessible(true);
    return $property->getValue($this->model);
}

public function validarImportacaoExcel(): array
{
    try {
        $empresaId = $this->empresaId();
        $usuarioId = $this->usuarioId();

        if (!isset($_FILES['ficheiro']) || !is_array($_FILES['ficheiro'])) {
            return [
                'success' => false,
                'mensagem' => 'Selecione um ficheiro Excel.'
            ];
        }

        $servico = new HorarioTrabalhoImportacaoService($this->getPdo());
        $resultado = $servico->validarArquivo($_FILES['ficheiro'], $empresaId, $usuarioId);

        return [
            'success' => true,
            'mensagem' => 'Ficheiro validado com sucesso.',
            'data' => $resultado
        ];
    } catch (Throwable $e) {
        return [
            'success' => false,
            'mensagem' => $e->getMessage()
        ];
    }
}

public function confirmarImportacaoExcel(): array
{
    try {
        $empresaId = $this->empresaId();
        $usuarioId = $this->usuarioId();
        $token = trim((string)($_POST['token'] ?? ''));

        if ($token === '') {
            return [
                'success' => false,
                'mensagem' => 'Token de importação inválido.'
            ];
        }

        $servico = new HorarioTrabalhoImportacaoService($this->getPdo());
        $resultado = $servico->confirmar($token, $empresaId, $usuarioId);

        return [
            'success' => true,
            'mensagem' => 'Importação de horários concluída com sucesso.',
            'data' => $resultado
        ];
    } catch (Throwable $e) {
        return [
            'success' => false,
            'mensagem' => 'Não foi possível concluir a importação: ' . $e->getMessage()
        ];
    }
}

public function baixarModeloImportacaoExcel(): void
{
    try {
        $empresaId = $this->empresaId();
        if ($empresaId <= 0) {
            throw new RuntimeException('Empresa inválida.');
        }

        $servico = new HorarioTrabalhoImportacaoService($this->getPdo());
        $spreadsheet = $servico->gerarModeloExcel($empresaId);

        if (ob_get_length()) {
            ob_end_clean();
        }

        $nomeArquivo = 'modelo_importacao_horarios_trabalho_' . date('Ymd_His') . '.xlsx';

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $nomeArquivo . '"');
        header('Cache-Control: max-age=0');
        header('Pragma: public');

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    } catch (Throwable $e) {
        http_response_code(500);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'mensagem' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

    // ==================================================
    // LISTAR
    // ==================================================

    public function listar(): array
    {
        return $this->model
            ->listar(
                $this->empresaId()
            );
    }

    // ==================================================
    // BUSCAR
    // ==================================================

    public function buscar(
        int $horarioId
    ): array {

        $this->validarHorarioId(
            $horarioId
        );

        return $this->model
            ->buscar(
                $this->empresaId(),
                $horarioId
            );
    }

    // ==================================================
    // CRIAR
    // ==================================================

    public function criar(
        array $dados
    ): array {

        $empresaId =
            $this->empresaId();

        $horarioId =
            $this->model
            ->criar(
                $empresaId,
                $this->usuarioId(),
                $dados
            );

        return [
            'id' =>
            $horarioId,

            'horario' =>
            $this->model
                ->buscar(
                    $empresaId,
                    $horarioId
                )
        ];
    }

    // ==================================================
    // EDITAR
    // ==================================================

    public function editar(
        int $horarioId,
        array $dados
    ): array {

        $this->validarHorarioId(
            $horarioId
        );

        $empresaId =
            $this->empresaId();

        $this->model
            ->editar(
                $empresaId,
                $this->usuarioId(),
                $horarioId,
                $dados
            );

        return [
            'id' =>
            $horarioId,

            'horario' =>
            $this->model
                ->buscar(
                    $empresaId,
                    $horarioId
                )
        ];
    }

    // ==================================================
    // ELIMINAR
    // ==================================================

    public function eliminar(
        int $horarioId
    ): array {

        $this->validarHorarioId(
            $horarioId
        );

        $this->model
            ->eliminar(
                $this->empresaId(),
                $this->usuarioId(),
                $horarioId
            );

        return [
            'id' =>
            $horarioId
        ];
    }

    // ==================================================
    // EMPRESA DA SESSÃO
    // ==================================================

    private function empresaId(): int
    {
        $empresaId =
            Auth::empresaId();

        if (
            $empresaId === null
            || $empresaId <= 0
        ) {
            throw new RuntimeException(
                'Empresa do utilizador não encontrada.'
            );
        }

        return (int)$empresaId;
    }

    // ==================================================
    // UTILIZADOR DA SESSÃO
    // ==================================================

    private function usuarioId(): int
    {
        $usuarioId =
            Auth::usuarioId();

        if (
            $usuarioId === null
            || $usuarioId <= 0
        ) {
            throw new RuntimeException(
                'Utilizador não autenticado.'
            );
        }

        return (int)$usuarioId;
    }

    // ==================================================
    // VALIDAR ID
    // ==================================================

    private function validarHorarioId(
        int $horarioId
    ): void {

        if ($horarioId <= 0) {
            throw new RuntimeException(
                'Horário de trabalho inválido.'
            );
        }
    }
}
