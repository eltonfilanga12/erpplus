<?php

declare(strict_types=1);

final class ObrigacaoFiscal
{
    public function __construct(private PDO $pdo) {}

    public function dadosIniciais(int $empresaId, ?int $filialId): array
    {
        $filtro = $filialId === null ? '' : ' AND (filial_id = :filial_id OR filial_id IS NULL)';

        $bancos = $this->consultar(
            "SELECT id, CONCAT(nome_banco, ' - ', conta) AS descricao
             FROM bancos WHERE empresa_id = :empresa_id {$filtro}
             ORDER BY nome_banco",
            $this->paramsEmpresaFilial($empresaId, $filialId)
        );

        $caixas = $this->consultar(
            "SELECT c.id, CONCAT(c.nome, ' - ', c.codigo) AS descricao
             FROM caixas c
             INNER JOIN abertura_caixa ac ON ac.caixa_id = c.id
             WHERE c.empresa_id = :empresa_id
               AND ac.data_fechamento IS NULL
               {$this->filtroFilial('c',$filialId)}
             GROUP BY c.id, c.nome, c.codigo ORDER BY c.nome",
            $this->paramsEmpresaFilial($empresaId, $filialId)
        );

        $filiais = $this->consultar(
            'SELECT id, nome FROM filiais WHERE empresa_id = :empresa_id ORDER BY nome',
            [':empresa_id' => $empresaId]
        );

        $centros = $this->consultar(
            "SELECT id, codigo, nome
             FROM centros_custo
             WHERE empresa_id = :empresa_id {$filtro}
             ORDER BY codigo, nome",
            $this->paramsEmpresaFilial($empresaId, $filialId)
        );

        return compact('bancos', 'caixas', 'filiais', 'centros');
    }

    public function listar(int $empresaId, ?int $filialId, string $tipo, int $ano): array
    {
        $this->validarTipo($tipo);
        if ($ano < 2000 || $ano > 2100) throw new InvalidArgumentException('Ano inválido.');

        /* A folha não possui filial_id. Para filtrar filial usamos os processamentos/funcionários. */
        $prefixo = $filialId === null ? 'fs' : 'pr';
        $trabalhadorExpr = $tipo === 'INSS'
            ? "COALESCE({$prefixo}.total_inss,0)"
            : "COALESCE({$prefixo}.total_irps,0)";
        $patronalExpr = $tipo === 'INSS'
            ? "COALESCE({$prefixo}.total_inss_patronal,0)"
            : '0';

        $sql = "
            SELECT
                fs.id AS folha_id,
                ps.descricao AS periodo,
                ps.ano,
                ps.mes,
                {$trabalhadorExpr} AS trabalhador,
                {$patronalExpr} AS patronal,
                ({$trabalhadorExpr}) + ({$patronalExpr}) AS apurado,
                COALESCE(pg.pago, 0) AS pago
            FROM folhas_salariais fs
            INNER JOIN periodos_salariais ps ON ps.id = fs.periodo_id
            INNER JOIN status sf ON sf.id = fs.status_id
            LEFT JOIN (
                SELECT folha_salarial_id, tipo, SUM(valor) AS pago
                FROM obrigacoes_fiscais_pagamentos
                WHERE empresa_id = :empresa_pagamentos
                " . ($filialId === null ? '' : ' AND filial_id = :filial_pagamentos') . "
                GROUP BY folha_salarial_id, tipo
            ) pg ON pg.folha_salarial_id = fs.id AND pg.tipo = :tipo_pagamento
            " . ($filialId === null ? '' : "LEFT JOIN (
                SELECT prs.periodo_id,
                       SUM(prs.total_inss) AS total_inss,
                       SUM(prs.total_inss_patronal) AS total_inss_patronal,
                       SUM(prs.total_irps) AS total_irps
                FROM processamentos_salariais prs
                INNER JOIN funcionarios fun ON fun.id = prs.funcionario_id
                WHERE prs.empresa_id = :empresa_processamentos
                  AND fun.filial_id = :filial_processamentos
                GROUP BY prs.periodo_id
            ) pr ON pr.periodo_id = fs.periodo_id") . "
            WHERE fs.empresa_id = :empresa_id
              AND ps.ano = :ano
              AND LOWER(sf.nome) <> 'cancelada'
        ";

        $params = [
            ':empresa_pagamentos' => $empresaId,
            ':tipo_pagamento' => $tipo,
            ':empresa_id' => $empresaId,
            ':ano' => $ano
        ];

        if ($filialId !== null) {
            $params[':filial_pagamentos'] = $filialId;
            $params[':empresa_processamentos'] = $empresaId;
            $params[':filial_processamentos'] = $filialId;
            $sql .= ' AND pr.periodo_id IS NOT NULL';
        }

        $sql .= ' ORDER BY ps.ano DESC, ps.mes DESC, fs.id DESC';
        $registos = $this->consultar($sql, $params);
        $resumo = ['trabalhador' => 0.0, 'patronal' => 0.0, 'apurado' => 0.0, 'pago' => 0.0, 'saldo' => 0.0];

        foreach ($registos as &$r) {
            foreach (['trabalhador', 'patronal', 'apurado', 'pago'] as $campo) $r[$campo] = round((float)$r[$campo], 2);
            $r['saldo'] = max(0, round($r['apurado'] - $r['pago'], 2));
            $r['estado'] = $r['saldo'] <= 0 ? 'PAGO' : ($r['pago'] > 0 ? 'PARCIAL' : 'PENDENTE');
            foreach ($resumo as $campo => $_) $resumo[$campo] += (float)($r[$campo] ?? 0);
        }
        unset($r);

        foreach ($resumo as &$valor) $valor = round($valor, 2);
        return compact('registos', 'resumo');
    }

    public function listarPagamentos(int $empresaId, ?int $filialId, int $folhaId, string $tipo): array
    {
        $this->validarTipo($tipo);
        if ($folhaId <= 0) throw new InvalidArgumentException('Folha salarial inválida.');

        $sql = "
            SELECT 
                p.id,
                p.fonte,
                p.valor,
                p.data_pagamento,
                p.referencia,
                p.documento,
                p.comprovativo,
                CASE 
                    WHEN p.fonte = 'BANCO' THEN CONCAT(b.nome_banco, ' - ', b.conta)
                    WHEN p.fonte = 'CAIXA' THEN CONCAT(c.nome, ' - ', c.codigo)
                    ELSE 'N/A'
                END AS conta_nome
            FROM obrigacoes_fiscais_pagamentos p
            LEFT JOIN bancos b ON b.id = p.banco_id
            LEFT JOIN caixas c ON c.id = p.caixa_id
            WHERE p.empresa_id = :empresa_id
              AND p.folha_salarial_id = :folha_id
              AND p.tipo = :tipo
              " . ($filialId === null ? '' : ' AND (p.filial_id = :filial_id OR p.filial_id IS NULL)') . "
            ORDER BY p.data_pagamento DESC, p.id DESC
        ";

        $params = [
            ':empresa_id' => $empresaId,
            ':folha_id'   => $folhaId,
            ':tipo'       => $tipo
        ];

        if ($filialId !== null) {
            $params[':filial_id'] = $filialId;
        }

        return $this->consultar($sql, $params);
    }

    public function pagar(int $empresaId, ?int $filialId, int $usuarioId, array $dados): int
    {
        $folhaId = (int)($dados['folha_id'] ?? 0);
        $tipo = strtoupper(trim((string)($dados['tipo'] ?? '')));
        $fonte = strtoupper(trim((string)($dados['fonte'] ?? '')));
        $valor = round((float)($dados['valor'] ?? 0), 2);
        $data = trim((string)($dados['data_pagamento'] ?? ''));
        $referencia = trim((string)($dados['referencia'] ?? ''));
        $bancoId = !empty($dados['banco_id']) ? (int)$dados['banco_id'] : null;
        $caixaId = !empty($dados['caixa_id']) ? (int)$dados['caixa_id'] : null;

        $this->validarTipo($tipo);
        if (!in_array($fonte, ['BANCO', 'CAIXA'], true)) throw new InvalidArgumentException('Fonte de pagamento inválida.');
        if ($folhaId <= 0 || $valor <= 0) throw new InvalidArgumentException('Folha ou valor inválido.');
        if ($referencia === '') throw new InvalidArgumentException('Informe a referência da guia.');
        if (($fonte === 'BANCO' && !$bancoId) || ($fonte === 'CAIXA' && !$caixaId)) throw new InvalidArgumentException('Selecione a conta de pagamento.');

        $momento = DateTimeImmutable::createFromFormat('Y-m-d\TH:i', $data)
            ?: DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $data);
        if (!$momento) throw new InvalidArgumentException('Data de pagamento inválida.');

        // 1. Processamento e upload do comprovativo (se fornecido)
        $caminhoComprovativo = null;
        $caminhoCompleto = null;

        if (isset($dados['FILES']['comprovativo']) && $dados['FILES']['comprovativo']['error'] === UPLOAD_ERR_OK) {
            $file = $dados['FILES']['comprovativo'];
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $extsPermitidas = ['pdf', 'png', 'jpg', 'jpeg'];

            if (!in_array($ext, $extsPermitidas, true)) {
                throw new InvalidArgumentException('Formato de comprovativo inválido. Use apenas PDF, PNG ou JPG.');
            }

            $diretorio = dirname(__DIR__, 2)
                . '/public/uploads/comprovativos_obrigacoes/';
            if (!is_dir($diretorio)) {
                mkdir($diretorio, 0755, true);
            }

            $nomeFicheiro = strtolower($tipo) . '_comprovativo_' . time() . '_' . uniqid() . '.' . $ext;
            $caminhoCompleto = $diretorio . $nomeFicheiro;

            if (move_uploaded_file($file['tmp_name'], $caminhoCompleto)) {
                $caminhoComprovativo = 'public/uploads/comprovativos_obrigacoes/' . $nomeFicheiro;
            }
        }

        $this->pdo->beginTransaction();
        try {
            $folha = $this->uma(
                "SELECT fs.*, ps.data_fim
                FROM folhas_salariais fs INNER JOIN periodos_salariais ps ON ps.id = fs.periodo_id
                WHERE fs.id = :id AND fs.empresa_id = :empresa_id FOR UPDATE",
                [':id' => $folhaId, ':empresa_id' => $empresaId]
            );
            if (!$folha) throw new RuntimeException('Folha salarial não encontrada.');

            if ($filialId === null) {
                $apurado = $tipo === 'INSS'
                    ? (float)$folha['total_inss'] + (float)$folha['total_inss_patronal']
                    : (float)$folha['total_irps'];
                $sqlPago = 'SELECT COALESCE(SUM(valor),0) FROM obrigacoes_fiscais_pagamentos WHERE empresa_id=:e AND folha_salarial_id=:f AND tipo=:t';
                $paramsPago = [':e' => $empresaId, ':f' => $folhaId, ':t' => $tipo];
            } else {
                $totaisFilial = $this->uma('SELECT SUM(pr.total_inss) total_inss, SUM(pr.total_inss_patronal) total_inss_patronal, SUM(pr.total_irps) total_irps FROM processamentos_salariais pr INNER JOIN funcionarios fun ON fun.id=pr.funcionario_id WHERE pr.empresa_id=:e AND pr.periodo_id=:p AND fun.filial_id=:filial', [':e' => $empresaId, ':p' => $folha['periodo_id'], ':filial' => $filialId]);
                $apurado = $tipo === 'INSS'
                    ? (float)($totaisFilial['total_inss'] ?? 0) + (float)($totaisFilial['total_inss_patronal'] ?? 0)
                    : (float)($totaisFilial['total_irps'] ?? 0);
                $sqlPago = 'SELECT COALESCE(SUM(valor),0) FROM obrigacoes_fiscais_pagamentos WHERE empresa_id=:e AND folha_salarial_id=:f AND tipo=:t AND filial_id=:filial';
                $paramsPago = [':e' => $empresaId, ':f' => $folhaId, ':t' => $tipo, ':filial' => $filialId];
            }
            $pago = (float)$this->escalar($sqlPago, $paramsPago);
            $saldo = round($apurado - $pago, 2);
            if ($saldo <= 0) throw new RuntimeException("A obrigação de {$tipo} já está paga.");
            if ($valor > $saldo) throw new RuntimeException('O valor informado ultrapassa o saldo pendente.');

            $config = $this->uma('SELECT * FROM configuracoes_contabilisticas_rh WHERE empresa_id=:e LIMIT 1', [':e' => $empresaId]);
            if (!$config) throw new RuntimeException('Configuração contabilística de RH não encontrada.');
            $contaObrigacao = (int)($tipo === 'INSS' ? $config['conta_inss_pagar_id'] : $config['conta_irps_pagar_id']);
            $contaFinanceira = (int)($fonte === 'BANCO' ? $config['conta_banco_padrao_id'] : $config['conta_caixa_padrao_id']);
            if ($contaObrigacao <= 0 || $contaFinanceira <= 0) throw new RuntimeException('Contas contabilísticas do pagamento não configuradas.');

            $periodo = $this->uma("SELECT pc.id, pc.exercicio_id FROM periodos_contabilisticos pc
                WHERE pc.empresa_id=:e AND :data BETWEEN pc.data_inicio AND pc.data_fim
                ORDER BY pc.data_inicio DESC LIMIT 1", [':e' => $empresaId, ':data' => $momento->format('Y-m-d')]);
            if (!$periodo) throw new RuntimeException('Não existe período contabilístico para a data do pagamento.');
            $diarioId = (int)$this->escalar("SELECT id FROM diarios_contabilisticos WHERE empresa_id=:e AND tipo='IMPOSTOS' ORDER BY id ASC LIMIT 1", [':e' => $empresaId]);
            if ($diarioId <= 0) throw new RuntimeException('Diário de Impostos não encontrado.');
            $statusId = (int)$this->escalar("SELECT id FROM status WHERE tipo='lancamento_contabilistico' AND LOWER(nome)='contabilizado' LIMIT 1", []);
            if ($statusId <= 0) throw new RuntimeException('Status Contabilizado não encontrado.');

            $uuid = $this->uuid();
            $stmt = $this->pdo->prepare('INSERT INTO obrigacoes_fiscais_pagamentos
                (uuid,empresa_id,filial_id,folha_salarial_id,tipo,fonte,banco_id,caixa_id,valor,data_pagamento,referencia,documento,observacoes,comprovativo,criado_por)
                VALUES (:uuid,:e,:filial,:folha,:tipo,:fonte,:banco,:caixa,:valor,:data,:ref,:doc,:obs,:comprovativo,:u)');
            $stmt->execute([
                ':uuid' => $uuid,
                ':e' => $empresaId,
                ':filial' => $filialId,
                ':folha' => $folhaId,
                ':tipo' => $tipo,
                ':fonte' => $fonte,
                ':banco' => $bancoId,
                ':caixa' => $caixaId,
                ':valor' => $valor,
                ':data' => $momento->format('Y-m-d H:i:s'),
                ':ref' => $referencia,
                ':doc' => $this->nulo($dados['documento'] ?? null),
                ':obs' => $this->nulo($dados['observacoes'] ?? null),
                ':comprovativo' => $caminhoComprovativo,
                ':u' => $usuarioId
            ]);
            $pagamentoId = (int)$this->pdo->lastInsertId();

            [$movBancoId, $movCaixaId, $aberturaId] = $this->gravarMovimentoFinanceiro($empresaId, $filialId, $usuarioId, $pagamentoId, $tipo, $fonte, $bancoId, $caixaId, $valor, $momento, $referencia, $dados);
            $lancamentoId = $this->gravarLancamento($empresaId, $filialId, $usuarioId, $pagamentoId, $tipo, $valor, $momento, $referencia, $periodo, $diarioId, $statusId, $contaObrigacao, $contaFinanceira);

            $up = $this->pdo->prepare('UPDATE obrigacoes_fiscais_pagamentos SET lancamento_contabilistico_id=:l,movimento_bancario_id=:mb,movimento_caixa_id=:mc,abertura_caixa_id=:ac WHERE id=:id');
            $up->execute([':l' => $lancamentoId, ':mb' => $movBancoId, ':mc' => $movCaixaId, ':ac' => $aberturaId, ':id' => $pagamentoId]);
            $this->pdo->commit();
            return $pagamentoId;
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();

            // Eliminar o ficheiro fisicamente caso a transação falhe
            if ($caminhoCompleto && file_exists($caminhoCompleto)) {
                @unlink($caminhoCompleto);
            }

            throw $e;
        }
    }

    private function gravarMovimentoFinanceiro(int $e, ?int $filial, int $u, int $pag, string $tipo, string $fonte, ?int $banco, ?int $caixa, float $valor, DateTimeImmutable $data, string $ref, array $dados): array
    {
        $descricao = "Pagamento de {$tipo} - guia {$ref}";
        $centroCustoId = $this->resolverCentroCustoId(
            $e,
            $filial,
            !empty($dados['centro_custo_id'])
                ? (int)$dados['centro_custo_id']
                : null
        );

        if ($fonte === 'BANCO') {
            $registro = $this->uma('SELECT saldo_atual FROM bancos WHERE id=:id AND empresa_id=:e FOR UPDATE', [':id' => $banco, ':e' => $e]);
            if (!$registro) throw new RuntimeException('Conta bancária inválida.');
            $anterior = (float)$registro['saldo_atual'];
            if ($anterior < $valor) throw new RuntimeException('Saldo bancário insuficiente.');
            $posterior = round($anterior - $valor, 2);
            $stmt = $this->pdo->prepare("INSERT INTO movimentos_bancarios (empresa_id,filial_id,centro_custo_id,banco_id,usuario_id,tipo,categoria,origem,origem_id,descricao,documento,referencia,valor,saldo_anterior,saldo_posterior,data_movimento,observacoes) VALUES (:e,:f,:cc,:b,:u,'SAIDA','PAGAMENTO','OBRIGACAO_FISCAL',:oid,:d,:doc,:r,:v,:sa,:sp,:data,:obs)");
            $stmt->execute([':e' => $e, ':f' => $filial, ':cc' => $centroCustoId, ':b' => $banco, ':u' => $u, ':oid' => $pag, ':d' => $descricao, ':doc' => $this->nulo($dados['documento'] ?? null), ':r' => $ref, ':v' => $valor, ':sa' => $anterior, ':sp' => $posterior, ':data' => $data->format('Y-m-d H:i:s'), ':obs' => $this->nulo($dados['observacoes'] ?? null)]);
            $id = (int)$this->pdo->lastInsertId();
            $this->pdo->prepare('UPDATE bancos SET saldo_atual=:s,atualizado_por=:u WHERE id=:id')->execute([':s' => $posterior, ':u' => $u, ':id' => $banco]);
            return [$id, null, null];
        }

        $abertura = $this->uma('SELECT id FROM abertura_caixa WHERE empresa_id=:e AND caixa_id=:c AND data_fechamento IS NULL ORDER BY data_abertura DESC LIMIT 1 FOR UPDATE', [':e' => $e, ':c' => $caixa]);
        if (!$abertura) throw new RuntimeException('O caixa selecionado não possui abertura ativa.');
        $saldo = (float)$this->escalar('SELECT saldo_atual FROM caixas WHERE id=:id AND empresa_id=:e FOR UPDATE', [':id' => $caixa, ':e' => $e]);
        if ($saldo < $valor) throw new RuntimeException('Saldo do caixa insuficiente.');
        $stmt = $this->pdo->prepare("INSERT INTO movimentos_caixa (empresa_id,filial_id,centro_custo_id,caixa_id,abertura_id,usuario_id,tipo,origem,documento_id,descricao,valor,data_movimento) VALUES (:e,:f,:cc,:c,:a,:u,'SAIDA','PAGAMENTO',:doc,:d,:v,:data)");
        $stmt->execute([':e' => $e, ':f' => $filial, ':cc' => $centroCustoId, ':c' => $caixa, ':a' => $abertura['id'], ':u' => $u, ':doc' => $pag, ':d' => $descricao, ':v' => $valor, ':data' => $data->format('Y-m-d H:i:s')]);
        $id = (int)$this->pdo->lastInsertId();
        $this->pdo->prepare('UPDATE caixas SET saldo_atual=saldo_atual-:v,atualizado_por=:u WHERE id=:id')->execute([':v' => $valor, ':u' => $u, ':id' => $caixa]);
        return [null, $id, (int)$abertura['id']];
    }

    private function resolverCentroCustoId(int $empresaId, ?int $filialId, ?int $centroCustoId): int
    {
        if ($centroCustoId !== null && $centroCustoId > 0) {
            $id = $this->escalar(
                'SELECT id FROM centros_custo
                 WHERE id=:id AND empresa_id=:e
                   AND (:filial IS NULL OR filial_id IS NULL OR filial_id=:filial2)
                 LIMIT 1',
                [':id' => $centroCustoId, ':e' => $empresaId, ':filial' => $filialId, ':filial2' => $filialId]
            );

            if ($id === false) {
                throw new RuntimeException('O centro de custo informado não pertence à empresa.');
            }

            return (int)$id;
        }

        $centros = $this->consultar(
            'SELECT id FROM centros_custo
             WHERE empresa_id=:e
               AND (:filial IS NULL OR filial_id IS NULL OR filial_id=:filial2)
             ORDER BY id ASC LIMIT 2',
            [':e' => $empresaId, ':filial' => $filialId, ':filial2' => $filialId]
        );

        if (count($centros) === 0) {
            throw new RuntimeException('Não existe centro de custo configurado para a empresa.');
        }

        if (count($centros) > 1) {
            throw new RuntimeException('Selecione o centro de custo do pagamento.');
        }

        return (int)$centros[0]['id'];
    }

    private function gravarLancamento(int $e, ?int $filial, int $u, int $pag, string $tipo, float $valor, DateTimeImmutable $data, string $ref, array $periodo, int $diario, int $status, int $contaObrigacao, int $contaFinanceira): int
    {
        $numero = (int)$this->escalar('SELECT COALESCE(MAX(numero),0)+1 FROM lancamentos_contabilisticos WHERE empresa_id=:e AND diario_id=:d', [':e' => $e, ':d' => $diario]);
        $descricao = "Pagamento de {$tipo} - guia {$ref}";
        $stmt = $this->pdo->prepare("INSERT INTO lancamentos_contabilisticos (empresa_id,filial_id,exercicio_id,periodo_id,diario_id,numero,numero_documento,referencia,data_lancamento,data_documento,descricao,origem,origem_id,status_id,total_debito,total_credito,contabilizado_em,contabilizado_por,criado_por,atualizado_por) VALUES (:e,:f,:ex,:p,:d,:n,:doc,:ref,:data,:data2,:descricao,'IMPOSTO',:origem,:s,:v,:v2,NOW(),:u,:u2,:u3)");
        $stmt->execute([':e' => $e, ':f' => $filial, ':ex' => $periodo['exercicio_id'], ':p' => $periodo['id'], ':d' => $diario, ':n' => $numero, ':doc' => "{$tipo}-{$pag}", ':ref' => $ref, ':data' => $data->format('Y-m-d'), ':data2' => $data->format('Y-m-d'), ':descricao' => $descricao, ':origem' => $pag, ':s' => $status, ':v' => $valor, ':v2' => $valor, ':u' => $u, ':u2' => $u, ':u3' => $u]);
        $id = (int)$this->pdo->lastInsertId();
        $item = $this->pdo->prepare('INSERT INTO lancamento_contabilistico_itens (lancamento_id,conta_contabil_id,descricao,debito,credito,ordem) VALUES (:l,:c,:d,:db,:cr,:o)');
        $item->execute([':l' => $id, ':c' => $contaObrigacao, ':d' => "Liquidação de {$tipo} a pagar", ':db' => $valor, ':cr' => 0, ':o' => 1]);
        $item->execute([':l' => $id, ':c' => $contaFinanceira, ':d' => "Saída financeira para pagamento de {$tipo}", ':db' => 0, ':cr' => $valor, ':o' => 2]);
        return $id;
    }

    private function validarTipo(string $tipo): void
    {
        if (!in_array($tipo, ['INSS', 'IRPS'], true)) throw new InvalidArgumentException('Tipo de obrigação inválido.');
    }
    private function filtroFilial(string $alias, ?int $f): string
    {
        return $f === null ? '' : " AND ({$alias}.filial_id = :filial_id OR {$alias}.filial_id IS NULL)";
    }
    private function paramsEmpresaFilial(int $e, ?int $f): array
    {
        $p = [':empresa_id' => $e];
        if ($f !== null) $p[':filial_id'] = $f;
        return $p;
    }
    private function consultar(string $sql, array $p): array
    {
        $s = $this->pdo->prepare($sql);
        $s->execute($p);
        return $s->fetchAll(PDO::FETCH_ASSOC);
    }
    private function uma(string $sql, array $p): ?array
    {
        $s = $this->pdo->prepare($sql);
        $s->execute($p);
        $r = $s->fetch(PDO::FETCH_ASSOC);
        return $r ?: null;
    }
    private function escalar(string $sql, array $p): mixed
    {
        $s = $this->pdo->prepare($sql);
        $s->execute($p);
        return $s->fetchColumn();
    }
    private function nulo(mixed $v): ?string
    {
        $v = trim((string)$v);
        return $v === '' ? null : $v;
    }
    private function uuid(): string
    {
        $d = random_bytes(16);
        $d[6] = chr((ord($d[6]) & 0x0f) | 0x40);
        $d[8] = chr((ord($d[8]) & 0x3f) | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($d), 4));
    }
}
