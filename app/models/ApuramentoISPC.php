<?php

declare(strict_types=1);

require_once __DIR__ . '/../services/AuditoriaService.php';
require_once __DIR__ . '/../services/LogService.php';

final class ApuramentoISPC
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
                ai.*,
                e.nome AS empresa_nome,
                f.nome AS filial_nome,
                s.nome AS status
            FROM apuramentos_ispc ai
            INNER JOIN empresas e ON e.id = ai.empresa_id
            LEFT JOIN filiais f ON f.id = ai.filial_id
            INNER JOIN status s ON s.id = ai.status_id
            WHERE ai.empresa_id = :empresa_id
        ";

        $params = [':empresa_id' => $empresaId];

        if ($filialId !== null) {
            $sql .= " AND ai.filial_id = :filial_id";
            $params[':filial_id'] = $filialId;
        }

        $sql .= " ORDER BY ai.exercicio_fiscal DESC, ai.id DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function buscar(int $id, int $empresaId): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT ai.*, s.nome AS status
            FROM apuramentos_ispc ai
            INNER JOIN status s ON s.id = ai.status_id
            WHERE ai.id = :id AND ai.empresa_id = :empresa_id
            LIMIT 1
        ");
        $stmt->execute([':id' => $id, ':empresa_id' => $empresaId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function calcularEApurar(array $dados): bool
    {
        $empresaId = (int)$dados['empresa_id'];
        $filialId = !empty($dados['filial_id']) ? (int)$dados['filial_id'] : null;
        $exercicio = (int)$dados['exercicio_fiscal'];
        $usuarioId = (int)$dados['usuario_id'];

        // 1. Buscar configuração fiscal da empresa/filial para obter a atividade e a faturação prevista ou real
        $stmtConfig = $this->pdo->prepare("
            SELECT tipo_atividade, previsao_faturacao_anual 
            FROM configuracoes_fiscais 
            WHERE empresa_id = :empresa_id 
              AND (filial_id = :filial_id OR filial_id IS NULL)
              AND exercicio_fiscal = :exercicio
            LIMIT 1
        ");
        $stmtConfig->execute([
            ':empresa_id' => $empresaId,
            ':filial_id' => $filialId,
            ':exercicio' => $exercicio
        ]);
        $config = $stmtConfig->fetch(PDO::FETCH_ASSOC);

        if (!$config) {
            throw new RuntimeException('Não foi encontrada nenhuma configuração fiscal ativa para este exercício e empresa/filial.');
        }

        $tipoAtividade = $config['tipo_atividade'];
        $volumeNegocios = (float)$config['previsao_faturacao_anual'];

        // 2. Determinar a taxa através da tabela de escalões do ISPC
        $stmtTaxa = $this->pdo->prepare("
            SELECT taxa_percentual 
            FROM escaloes_ispc 
            WHERE tipo_atividade = :tipo 
              AND :faturacao >= limite_inferior 
              AND (:faturacao <= limite_superior OR limite_superior IS NULL)
            LIMIT 1
        ");
        $stmtTaxa->execute([':tipo' => $tipoAtividade, ':faturacao' => $volumeNegocios]);
        $taxaAplicada = (float)$stmtTaxa->fetchColumn();

        if ($taxaAplicada <= 0) {
            throw new RuntimeException('Não foi possível determinar a taxa de ISPC aplicável para o volume de negócios informado.');
        }

        // 3. Calcular o imposto apurado
        $impostoApurado = round($volumeNegocios * ($taxaAplicada / 100), 2);

        // ID de status padrão para 'Pendente' (ex: ID 204 vindo da tabela status partilhada)
        $statusId = 204; 

        $this->pdo->beginTransaction();
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO apuramentos_ispc (
                    empresa_id, filial_id, exercicio_fiscal, volume_negocios,
                    taxa_aplicada, imposto_apurado, imposto_pago, saldo_devido,
                    status_id, criado_por
                ) VALUES (
                    :empresa_id, :filial_id, :exercicio_fiscal, :volume_negocios,
                    :taxa_aplicada, :imposto_apurado, 0.00, :saldo_devido,
                    :status_id, :criado_por
                )
            ");

            $stmt->execute([
                ':empresa_id' => $empresaId,
                ':filial_id' => $filialId,
                ':exercicio_fiscal' => $exercicio,
                ':volume_negocios' => $volumeNegocios,
                ':taxa_aplicada' => $taxaAplicada,
                ':imposto_apurado' => $impostoApurado,
                ':saldo_devido' => $impostoApurado,
                ':status_id' => $statusId,
                ':criado_por' => $usuarioId
            ]);

            $id = (int)$this->pdo->lastInsertId();

            $this->log->registrar(
                $empresaId,
                $usuarioId,
                'APURAMENTO_ISPC',
                'APURAR',
                'Apuramento de ISPC efetuado para o ano ' . $exercicio . ' no valor de ' . $impostoApurado . ' MT',
                $id,
                $filialId
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
}