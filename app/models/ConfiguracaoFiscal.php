<?php

declare(strict_types=1);

require_once __DIR__ . '/../services/AuditoriaService.php';
require_once __DIR__ . '/../services/LogService.php';

final class ConfiguracaoFiscal
{
    private PDO $pdo;
    private AuditoriaService $auditoria;
    private LogService $log;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->auditoria = new AuditoriaService($pdo);
        $this->log = new LogService($pdo);
    }

    public function listar(int $empresaId, ?int $filialId = null): array
    {
        $sql = "
            SELECT 
                cf.*,
                e.nome AS empresa_nome,
                f.nome AS filial_nome,
                s.nome AS status
            FROM configuracoes_fiscais cf
            INNER JOIN empresas e ON e.id = cf.empresa_id
            LEFT JOIN filiais f ON f.id = cf.filial_id
            INNER JOIN status s ON s.id = cf.estado_id
            WHERE cf.empresa_id = :empresa_id
        ";

        $params = [':empresa_id' => $empresaId];

        if ($filialId !== null) {
            $sql .= " AND cf.filial_id = :filial_id";
            $params[':filial_id'] = $filialId;
        }

        $sql .= " ORDER BY cf.exercicio_fiscal DESC, cf.id DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function buscar(int $id, int $empresaId): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT cf.*, s.nome AS status
            FROM configuracoes_fiscais cf
            INNER JOIN status s ON s.id = cf.estado_id
            WHERE cf.id = :id AND cf.empresa_id = :empresa_id
            LIMIT 1
        ");
        $stmt->execute([':id' => $id, ':empresa_id' => $empresaId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function criar(array $dados): bool
    {
        $this->pdo->beginTransaction();
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO configuracoes_fiscais (
                    empresa_id, filial_id, regime_tributario, tipo_atividade,
                    sujeito_passivo_iva, numero_trabalhadores, previsao_faturacao_anual,
                    exercicio_fiscal, estado_id, criado_por
                ) VALUES (
                    :empresa_id, :filial_id, :regime_tributario, :tipo_atividade,
                    :sujeito_passivo_iva, :numero_trabalhadores, :previsao_faturacao_anual,
                    :exercicio_fiscal, :estado_id, :criado_por
                )
            ");

            $stmt->execute([
                ':empresa_id' => $dados['empresa_id'],
                ':filial_id' => !empty($dados['filial_id']) ? (int)$dados['filial_id'] : null,
                ':regime_tributario' => $dados['regime_tributario'],
                ':tipo_atividade' => $dados['tipo_atividade'],
                ':sujeito_passivo_iva' => (int)$dados['sujeito_passivo_iva'],
                ':numero_trabalhadores' => (int)$dados['numero_trabalhadores'],
                ':previsao_faturacao_anual' => (float)$dados['previsao_faturacao_anual'],
                ':exercicio_fiscal' => (int)$dados['exercicio_fiscal'],
                ':estado_id' => (int)$dados['estado_id'],
                ':criado_por' => $dados['usuario_id']
            ]);

            $id = (int)$this->pdo->lastInsertId();

            $this->log->registrar(
                (int)$dados['empresa_id'],
                (int)$dados['usuario_id'],
                'CONFIG_FISCAL',
                'CRIAR',
                'Configuração fiscal criada para o exercício ' . $dados['exercicio_fiscal'],
                $id,
                !empty($dados['filial_id']) ? (int)$dados['filial_id'] : null
            );

            $this->pdo->commit();
            return true;
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    public function atualizar(array $dados): bool
    {
        $this->pdo->beginTransaction();
        try {
            $stmt = $this->pdo->prepare("
                UPDATE configuracoes_fiscais SET
                    filial_id = :filial_id,
                    regime_tributario = :regime_tributario,
                    tipo_atividade = :tipo_atividade,
                    sujeito_passivo_iva = :sujeito_passivo_iva,
                    numero_trabalhadores = :numero_trabalhadores,
                    previsao_faturacao_anual = :previsao_faturacao_anual,
                    exercicio_fiscal = :exercicio_fiscal,
                    estado_id = :estado_id
                WHERE id = :id AND empresa_id = :empresa_id
            ");

            $stmt->execute([
                ':filial_id' => !empty($dados['filial_id']) ? (int)$dados['filial_id'] : null,
                ':regime_tributario' => $dados['regime_tributario'],
                ':tipo_atividade' => $dados['tipo_atividade'],
                ':sujeito_passivo_iva' => (int)$dados['sujeito_passivo_iva'],
                ':numero_trabalhadores' => (int)$dados['numero_trabalhadores'],
                ':previsao_faturacao_anual' => (float)$dados['previsao_faturacao_anual'],
                ':exercicio_fiscal' => (int)$dados['exercicio_fiscal'],
                ':estado_id' => (int)$dados['estado_id'],
                ':id' => (int)$dados['id'],
                ':empresa_id' => (int)$dados['empresa_id']
            ]);

            $this->log->registrar(
                (int)$dados['empresa_id'],
                (int)$dados['usuario_id'],
                'CONFIG_FISCAL',
                'EDITAR',
                'Configuração fiscal atualizada para o exercício ' . $dados['exercicio_fiscal'],
                (int)$dados['id'],
                !empty($dados['filial_id']) ? (int)$dados['filial_id'] : null
            );

            $this->pdo->commit();
            return true;
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    public function calcularTaxaISPC(string $tipoAtividade, float $faturacao): float
    {
        $stmt = $this->pdo->prepare("
            SELECT taxa_percentual 
            FROM escaloes_ispc 
            WHERE tipo_atividade = :tipo 
              AND :faturacao >= limite_inferior 
              AND (:faturacao <= limite_superior OR limite_superior IS NULL)
            LIMIT 1
        ");
        $stmt->execute([':tipo' => $tipoAtividade, ':faturacao' => $faturacao]);
        $taxa = $stmt->fetchColumn();
        
        return $taxa !== false ? (float)$taxa : 0.00;
    }

    public function listarStatus(): array
    {
        $stmt = $this->pdo->prepare("
            SELECT id, tipo, nome, descricao 
            FROM status 
            WHERE tipo IN ('empresa', 'filial', 'configuracao')
            ORDER BY nome ASC
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}