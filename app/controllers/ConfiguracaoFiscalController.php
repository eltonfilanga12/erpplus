<?php

declare(strict_types=1);

final class ConfiguracaoFiscalController
{
    private ConfiguracaoFiscal $configFiscal;

    public function __construct(PDO $pdo)
    {
        $this->configFiscal = new ConfiguracaoFiscal($pdo);
    }

    public function listar(array $dados = []): array
    {
        $empresaId = (int)Auth::empresaId();
        if ($empresaId <= 0) {
            throw new RuntimeException('Empresa do utilizador não encontrada.');
        }
        $filialId = !empty($dados['filial_id']) ? (int)$dados['filial_id'] : null;

        return $this->configFiscal->listar($empresaId, $filialId);
    }

    public function buscar(array $dados): ?array
    {
        $id = (int)($dados['id'] ?? 0);
        $empresaId = (int)Auth::empresaId();
        if ($id <= 0) {
            throw new RuntimeException('Registo fiscal inválido.');
        }
        return $this->configFiscal->buscar($id, $empresaId);
    }

    public function guardar(array $dados): bool
    {
        $dados['empresa_id'] = (int)Auth::empresaId();
        $dados['usuario_id'] = (int)Auth::usuarioId();

        if (empty($dados['regime_tributario'])) {
            throw new RuntimeException('Selecione o regime tributário.');
        }

        if (!empty($dados['id'])) {
            return $this->configFiscal->atualizar($dados);
        }

        return $this->configFiscal->criar($dados);
    }

    public function calcularTaxa(array $dados): float
    {
        $tipo = $dados['tipo_atividade'] ?? '';
        $faturacao = (float)($dados['previsao_faturacao_anual'] ?? 0);
        return $this->configFiscal->calcularTaxaISPC($tipo, $faturacao);
    }

    public function listarStatus(): array
    {
        return $this->configFiscal->listarStatus();
    }
}