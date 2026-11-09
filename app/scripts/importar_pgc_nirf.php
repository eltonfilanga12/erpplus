<?php

declare(strict_types=1);

require_once __DIR__
    . '/../core/Database.php';

require_once __DIR__
    . '/../services/LogService.php';

final class ImportadorPgcNirf
{
    private PDO $db;

    private LogService $log;

    private int $empresaId;
    private int $planoId;
    private int $statusId;
    private ?int $usuarioId;

    /**
     * Natureza e grupo padrão de cada classe.
     */
    private const CONFIGURACAO_CLASSES = [
        '1' => [
            'natureza' => 'DEVEDORA',
            'grupo' => 'ATIVO',
            'classificacao' => 'CORRENTE',
        ],

        '2' => [
            'natureza' => 'DEVEDORA',
            'grupo' => 'ATIVO',
            'classificacao' => 'CORRENTE',
        ],

        '3' => [
            'natureza' => 'DEVEDORA',
            'grupo' => 'ATIVO',
            'classificacao' => 'NAO_CORRENTE',
        ],

        '4' => [
            'natureza' => 'DEVEDORA',
            'grupo' => 'ATIVO',
            'classificacao' => 'NAO_APLICAVEL',
        ],

        '5' => [
            'natureza' => 'CREDORA',
            'grupo' => 'CAPITAL_PROPRIO',
            'classificacao' => 'NAO_APLICAVEL',
        ],

        '6' => [
            'natureza' => 'DEVEDORA',
            'grupo' => 'GASTO',
            'classificacao' => 'NAO_APLICAVEL',
        ],

        '7' => [
            'natureza' => 'CREDORA',
            'grupo' => 'RENDIMENTO',
            'classificacao' => 'NAO_APLICAVEL',
        ],

        '8' => [
            'natureza' => 'CREDORA',
            'grupo' => 'RESULTADO',
            'classificacao' => 'NAO_APLICAVEL',
        ],
    ];

    public function __construct(
        int $empresaId,
        int $planoId,
        int $statusId,
        ?int $usuarioId = null
    ) {
        $this->db =
            Database::conn();

        $this->db->setAttribute(
            PDO::ATTR_ERRMODE,
            PDO::ERRMODE_EXCEPTION
        );

        $this->log =
            new LogService(
                $this->db
            );

        $this->empresaId =
            $empresaId;

        $this->planoId =
            $planoId;

        $this->statusId =
            $statusId;

        $this->usuarioId =
            $usuarioId;
    }

    /**
     * Importa todas as contas recebidas.
     */
    public function importar(array $contas): array
    {
        if (empty($contas)) {
            throw new InvalidArgumentException(
                'Nenhuma conta foi fornecida para importação.'
            );
        }

        $this->validarPlano();

        /*
         * Garante que as contas-pai sejam inseridas primeiro.
         */
        usort(
            $contas,
            static function (array $a, array $b): int {
                $nivelA = self::calcularNivel(
                    (string)($a['codigo'] ?? '')
                );

                $nivelB = self::calcularNivel(
                    (string)($b['codigo'] ?? '')
                );

                if ($nivelA === $nivelB) {
                    return strnatcasecmp(
                        (string)$a['codigo'],
                        (string)$b['codigo']
                    );
                }

                return $nivelA <=> $nivelB;
            }
        );

        $resultado = [
            'inseridas' => 0,
            'atualizadas' => 0,
            'ignoradas' => 0,
            'erros' => [],
        ];

        try {
            $this->db->beginTransaction();

            foreach ($contas as $indice => $conta) {
                try {
                    $operacao = $this->guardarConta($conta);

                    if ($operacao === 'INSERIDA') {
                        $resultado['inseridas']++;
                    } elseif ($operacao === 'ATUALIZADA') {
                        $resultado['atualizadas']++;
                    } else {
                        $resultado['ignoradas']++;
                    }
                } catch (Throwable $erro) {
                    $resultado['erros'][] = [
                        'linha' => $indice + 1,
                        'codigo' => $conta['codigo'] ?? null,
                        'mensagem' => $erro->getMessage(),
                    ];

                    throw $erro;
                }
            }

            $this->atualizarTiposSinteticos();


            /*
|--------------------------------------------------------------------------
| Log único da importação
|--------------------------------------------------------------------------
*/

            if (
                $this->usuarioId !== null
                && $this->usuarioId > 0
            ) {
                $this->log->registrar(
                    $this->empresaId,
                    $this->usuarioId,
                    'PLANO_CONTAS',
                    'IMPORTAR_PGC_NIRF',
                    sprintf(
                        'Importou o PGC-NIRF para o plano de contas #%d. Inseridas: %d, atualizadas: %d, ignoradas: %d.',
                        $this->planoId,
                        (int)$resultado['inseridas'],
                        (int)$resultado['atualizadas'],
                        (int)$resultado['ignoradas']
                    ),
                    $this->planoId
                );
            }


            $this->db->commit();


            return $resultado;
        } catch (Throwable $erro) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            throw $erro;
        }
    }

    /**
     * Insere ou atualiza uma conta.
     */
    private function guardarConta(array $dados): string
    {
        $codigo = $this->normalizarCodigo(
            (string)($dados['codigo'] ?? '')
        );

        $nome = trim(
            (string)($dados['nome'] ?? '')
        );

        if ($codigo === '') {
            throw new InvalidArgumentException(
                'O código da conta não foi informado.'
            );
        }

        if ($nome === '') {
            throw new InvalidArgumentException(
                "O nome da conta {$codigo} não foi informado."
            );
        }

        $nivel = self::calcularNivel($codigo);

        $classe = explode('.', $codigo)[0];

        if (!isset(self::CONFIGURACAO_CLASSES[$classe])) {
            throw new InvalidArgumentException(
                "A classe da conta {$codigo} não é válida."
            );
        }

        $configuracao = $this->resolverClassificacao(
            $codigo
        );

        $codigoPai = $this->obterCodigoPai($codigo);

        $contaPaiId = null;

        if ($codigoPai !== null) {
            $contaPaiId = $this->buscarIdPorCodigo(
                $codigoPai
            );

            if ($contaPaiId === null) {
                throw new RuntimeException(
                    "A conta-pai {$codigoPai} da conta {$codigo} " .
                        'ainda não foi cadastrada.'
                );
            }
        }

        $natureza = strtoupper(
            trim(
                (string)(
                    $dados['natureza']
                    ?? $configuracao['natureza']
                )
            )
        );

        $grupo = strtoupper(
            trim(
                (string)(
                    $dados['grupo']
                    ?? $configuracao['grupo']
                )
            )
        );

        $classificacao = strtoupper(
            trim(
                (string)(
                    $dados['classificacao_patrimonial']
                    ?? $configuracao['classificacao']
                )
            )
        );

        $integracaoCodigo = trim(
            (string)($dados['integracao_codigo'] ?? '')
        );

        $existente = $this->buscarPorCodigo(
            $codigo
        );

        if ($existente !== null) {
            $this->atualizarConta(
                (int)$existente['id'],
                [
                    'conta_pai_id' => $contaPaiId,
                    'nome' => $nome,
                    'descricao' => $dados['descricao'] ?? null,
                    'nivel' => $nivel,
                    'classe' => $classe,
                    'natureza' => $natureza,
                    'grupo' => $grupo,
                    'classificacao' => $classificacao,
                    'integracao_codigo' => $integracaoCodigo !== ''
                        ? $integracaoCodigo
                        : null,
                    'conta_caixa' => (int)($dados['conta_caixa'] ?? 0),
                    'conta_banco' => (int)($dados['conta_banco'] ?? 0),
                    'conta_cliente' => (int)($dados['conta_cliente'] ?? 0),
                    'conta_fornecedor' => (int)($dados['conta_fornecedor'] ?? 0),
                    'conta_imposto' => (int)($dados['conta_imposto'] ?? 0),
                    'exige_centro_custo' => (int)(
                        $dados['exige_centro_custo'] ?? 0
                    ),
                    'exige_entidade' => (int)(
                        $dados['exige_entidade'] ?? 0
                    ),
                    'fluxo_caixa_categoria' => strtoupper(
                        trim(
                            (string)(
                                $dados['fluxo_caixa_categoria']
                                ?? 'NENHUM'
                            )
                        )
                    ),

                    'fluxo_caixa_descricao' =>
                    $dados['fluxo_caixa_descricao']
                        ?? null,
                ]
            );

            return 'ATUALIZADA';
        }

        $sql = "
            INSERT INTO contas_contabilisticas
            (
                empresa_id,
                plano_contas_id,
                conta_pai_id,
                codigo,
                nome,
                descricao,
                nivel,
                classe,
                integracao_codigo,
                tipo,
                natureza,
                grupo,
                classificacao_patrimonial,
                aceita_lancamento,
                exige_centro_custo,
                exige_entidade,
                conta_caixa,
                conta_banco,
                conta_cliente,
                conta_fornecedor,
                conta_imposto,
                fluxo_caixa_categoria,
                fluxo_caixa_descricao,
                status_id,
                criado_por,
                atualizado_por
            )
            VALUES
            (
                :empresa_id,
                :plano_contas_id,
                :conta_pai_id,
                :codigo,
                :nome,
                :descricao,
                :nivel,
                :classe,
                :integracao_codigo,
                'ANALITICA',
                :natureza,
                :grupo,
                :classificacao_patrimonial,
                1,
                :exige_centro_custo,
                :exige_entidade,
                :conta_caixa,
                :conta_banco,
                :conta_cliente,
                :conta_fornecedor,
                :conta_imposto,
                :fluxo_caixa_categoria,
                :fluxo_caixa_descricao,
                :status_id,
                :criado_por,
                :atualizado_por
            )
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':empresa_id' => $this->empresaId,
            ':plano_contas_id' => $this->planoId,
            ':conta_pai_id' => $contaPaiId,
            ':codigo' => $codigo,
            ':nome' => $nome,
            ':descricao' => $this->textoOuNull(
                $dados['descricao'] ?? null
            ),
            ':nivel' => $nivel,
            ':classe' => $classe,
            ':integracao_codigo' => $integracaoCodigo !== ''
                ? $integracaoCodigo
                : null,
            ':natureza' => $natureza,
            ':grupo' => $grupo,
            ':classificacao_patrimonial' => $classificacao,
            ':exige_centro_custo' => (int)(
                $dados['exige_centro_custo'] ?? 0
            ),
            ':exige_entidade' => (int)(
                $dados['exige_entidade'] ?? 0
            ),
            ':conta_caixa' => (int)($dados['conta_caixa'] ?? 0),
            ':conta_banco' => (int)($dados['conta_banco'] ?? 0),
            ':conta_cliente' => (int)($dados['conta_cliente'] ?? 0),
            ':conta_fornecedor' => (int)(
                $dados['conta_fornecedor'] ?? 0
            ),
            ':conta_imposto' => (int)($dados['conta_imposto'] ?? 0),
            ':fluxo_caixa_categoria' => strtoupper(
                trim(
                    (string)(
                        $dados['fluxo_caixa_categoria']
                        ?? 'NENHUM'
                    )
                )
            ),
            ':fluxo_caixa_descricao' => $this->textoOuNull(
                $dados['fluxo_caixa_descricao'] ?? null
            ),
            ':status_id' => $this->statusId,
            ':criado_por' => $this->usuarioId,
            ':atualizado_por' => $this->usuarioId,
        ]);

        return 'INSERIDA';
    }


    private function resolverClassificacao(
        string $codigo
    ): array {
        $classe = explode('.', $codigo)[0];

        $configuracao = self::CONFIGURACAO_CLASSES[$classe]
            ?? null;

        if ($configuracao === null) {
            throw new InvalidArgumentException(
                "A classe da conta {$codigo} não é válida."
            );
        }

        /*
     * Classe 3:
     * amortizações e imparidades são contas redutoras
     * de ativos, normalmente de natureza credora.
     */
        if (
            str_starts_with($codigo, '3.8') ||
            str_starts_with($codigo, '3.9')
        ) {
            return [
                'natureza' => 'CREDORA',
                'grupo' => 'ATIVO',
                'classificacao' => 'NAO_CORRENTE',
            ];
        }

        /*
     * Classe 4 — contas predominantemente ativas.
     */
        if (
            str_starts_with($codigo, '4.1') ||
            str_starts_with($codigo, '4.5') ||
            str_starts_with($codigo, '4.7')
        ) {
            return [
                'natureza' => 'DEVEDORA',
                'grupo' => 'ATIVO',
                'classificacao' => 'CORRENTE',
            ];
        }

        /*
     * Empréstimos obtidos, fornecedores, credores,
     * provisões e acréscimos de gastos.
     */
        if (
            str_starts_with($codigo, '4.2') ||
            str_starts_with($codigo, '4.3') ||
            str_starts_with($codigo, '4.6') ||
            str_starts_with($codigo, '4.8') ||
            str_starts_with($codigo, '4.9.1') ||
            str_starts_with($codigo, '4.9.2')
        ) {
            return [
                'natureza' => 'CREDORA',
                'grupo' => 'PASSIVO',
                'classificacao' => 'CORRENTE',
            ];
        }

        /*
     * Acréscimos de rendimentos e gastos diferidos.
     */
        if (
            str_starts_with($codigo, '4.9.3') ||
            str_starts_with($codigo, '4.9.4')
        ) {
            return [
                'natureza' => 'DEVEDORA',
                'grupo' => 'ATIVO',
                'classificacao' => 'CORRENTE',
            ];
        }

        /*
     * Estado: algumas contas podem ser devedoras
     * ou credoras. Tratamos os principais casos.
     */
        if (str_starts_with($codigo, '4.4')) {
            if (
                str_starts_with($codigo, '4.4.1.2') ||
                str_starts_with($codigo, '4.4.1.3') ||
                str_starts_with($codigo, '4.4.3.1') ||
                str_starts_with($codigo, '4.4.3.2') ||
                str_starts_with($codigo, '4.4.3.8') ||
                str_starts_with($codigo, '4.4.3.9') ||
                str_starts_with($codigo, '4.4.6.1')
            ) {
                return [
                    'natureza' => 'DEVEDORA',
                    'grupo' => 'ATIVO',
                    'classificacao' => 'CORRENTE',
                ];
            }

            return [
                'natureza' => 'CREDORA',
                'grupo' => 'PASSIVO',
                'classificacao' => 'CORRENTE',
            ];
        }

        return $configuracao;
    }

    /**
     * Atualiza uma conta existente.
     */
    private function atualizarConta(
        int $id,
        array $dados
    ): void {
        $sql = "
            UPDATE contas_contabilisticas
            SET
                conta_pai_id = :conta_pai_id,
                nome = :nome,
                descricao = :descricao,
                nivel = :nivel,
                classe = :classe,
                integracao_codigo = COALESCE(
                    :integracao_codigo,
                    integracao_codigo
                ),
                natureza = :natureza,
                grupo = :grupo,
                classificacao_patrimonial = :classificacao,
                exige_centro_custo = :exige_centro_custo,
                exige_entidade = :exige_entidade,
                conta_caixa = :conta_caixa,
                conta_banco = :conta_banco,
                conta_cliente = :conta_cliente,
                conta_fornecedor = :conta_fornecedor,
                conta_imposto = :conta_imposto,

                fluxo_caixa_categoria =
                    :fluxo_caixa_categoria,

                fluxo_caixa_descricao =
                    :fluxo_caixa_descricao,

                atualizado_por = :atualizado_por
            WHERE id = :id
              AND empresa_id = :empresa_id
              AND plano_contas_id = :plano_contas_id
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':conta_pai_id' => $dados['conta_pai_id'],
            ':nome' => $dados['nome'],
            ':descricao' => $this->textoOuNull(
                $dados['descricao']
            ),
            ':nivel' => $dados['nivel'],
            ':classe' => $dados['classe'],
            ':integracao_codigo' => $dados['integracao_codigo'],
            ':natureza' => $dados['natureza'],
            ':grupo' => $dados['grupo'],
            ':classificacao' => $dados['classificacao'],
            ':exige_centro_custo' => $dados['exige_centro_custo'],
            ':exige_entidade' => $dados['exige_entidade'],
            ':conta_caixa' => $dados['conta_caixa'],
            ':conta_banco' => $dados['conta_banco'],
            ':conta_cliente' => $dados['conta_cliente'],
            ':conta_fornecedor' => $dados['conta_fornecedor'],
            ':conta_imposto' => $dados['conta_imposto'],
            ':fluxo_caixa_categoria' =>
            $dados['fluxo_caixa_categoria'],

            ':fluxo_caixa_descricao' =>
            $this->textoOuNull(
                $dados['fluxo_caixa_descricao']
            ),
            ':atualizado_por' => $this->usuarioId,
            ':id' => $id,
            ':empresa_id' => $this->empresaId,
            ':plano_contas_id' => $this->planoId,
        ]);
    }

    /**
     * Todas as contas que possuem filhos tornam-se sintéticas.
     */
    private function atualizarTiposSinteticos(): void
    {
        $sql = "
            UPDATE contas_contabilisticas pai
            SET
                pai.tipo = 'SINTETICA',
                pai.aceita_lancamento = 0,
                pai.atualizado_por = :usuario_id
            WHERE pai.empresa_id = :empresa_id
              AND pai.plano_contas_id = :plano_contas_id
              AND EXISTS (
                  SELECT 1
                  FROM contas_contabilisticas filha
                  WHERE filha.conta_pai_id = pai.id
              )
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':usuario_id' => $this->usuarioId,
            ':empresa_id' => $this->empresaId,
            ':plano_contas_id' => $this->planoId,
        ]);

        /*
         * Contas sem filhos ficam analíticas.
         */
        $sql = "
            UPDATE contas_contabilisticas conta
            SET
                conta.tipo = 'ANALITICA',
                conta.aceita_lancamento = 1,
                conta.atualizado_por = :usuario_id
            WHERE conta.empresa_id = :empresa_id
              AND conta.plano_contas_id = :plano_contas_id
              AND NOT EXISTS (
                  SELECT 1
                  FROM contas_contabilisticas filha
                  WHERE filha.conta_pai_id = conta.id
              )
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':usuario_id' => $this->usuarioId,
            ':empresa_id' => $this->empresaId,
            ':plano_contas_id' => $this->planoId,
        ]);
    }

    private function validarPlano(): void
    {
        $stmt = $this->db->prepare("
            SELECT id
            FROM planos_contas
            WHERE id = :plano_id
              AND empresa_id = :empresa_id
            LIMIT 1
        ");

        $stmt->execute([
            ':plano_id' => $this->planoId,
            ':empresa_id' => $this->empresaId,
        ]);

        if (!$stmt->fetchColumn()) {
            throw new RuntimeException(
                'O plano PGC-NIRF não foi encontrado para a empresa.'
            );
        }
    }

    private function buscarPorCodigo(
        string $codigo
    ): ?array {
        $stmt = $this->db->prepare("
            SELECT id, codigo
            FROM contas_contabilisticas
            WHERE empresa_id = :empresa_id
              AND plano_contas_id = :plano_contas_id
              AND codigo = :codigo
            LIMIT 1
        ");

        $stmt->execute([
            ':empresa_id' => $this->empresaId,
            ':plano_contas_id' => $this->planoId,
            ':codigo' => $codigo,
        ]);

        $conta = $stmt->fetch(PDO::FETCH_ASSOC);

        return $conta ?: null;
    }

    private function buscarIdPorCodigo(
        string $codigo
    ): ?int {
        $conta = $this->buscarPorCodigo($codigo);

        return $conta !== null
            ? (int)$conta['id']
            : null;
    }

    private static function calcularNivel(
        string $codigo
    ): int {
        $codigo = trim($codigo);

        if ($codigo === '') {
            return 0;
        }

        return substr_count($codigo, '.') + 1;
    }

    private function obterCodigoPai(
        string $codigo
    ): ?string {
        if (!str_contains($codigo, '.')) {
            return null;
        }

        $partes = explode('.', $codigo);

        array_pop($partes);

        return implode('.', $partes);
    }

    private function normalizarCodigo(
        string $codigo
    ): string {
        $codigo = trim($codigo);

        $codigo = preg_replace(
            '/\s+/',
            '',
            $codigo
        );

        return trim(
            (string)$codigo,
            '.'
        );
    }

    private function textoOuNull(
        mixed $valor
    ): ?string {
        $texto = trim((string)$valor);

        return $texto !== ''
            ? $texto
            : null;
    }
}
