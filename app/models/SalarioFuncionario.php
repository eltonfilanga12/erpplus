<?php

require_once __DIR__ . '/../core/Database.php';

class SalarioFuncionario
{
    private PDO $db;

    private const TIPO_STATUS = 'salario_funcionario';

    private const STATUS_ATIVO   = 'Activo';
    private const STATUS_INATIVO = 'Inactivo';

    public function __construct()
    {
        $this->db = Database::conn();
    }

    /**
     * Lista configurações salariais
     */
    public function listar(array $filtros = []): array
    {
        $sql = "
        SELECT
            sf.id,
            sf.uuid,
            sf.empresa_id,
            sf.funcionario_id,

            e.nome AS empresa,

            f.nome_completo AS funcionario,

            ct.numero_contrato AS contrato,

            sf.moeda,
            sf.salario_base,
            sf.periodicidade,
            sf.horas_mensais,
            sf.dias_mensais,
            sf.data_inicio,
            sf.data_fim,

            st.nome AS estado,

            COALESCE(
                totais.total_proventos,
                0
            ) AS total_proventos,

            COALESCE(
                totais.total_descontos,
                0
            ) AS total_descontos,

            (
                sf.salario_base
                +
                COALESCE(
                    totais.total_proventos,
                    0
                )
            ) AS salario_bruto,

            (
                sf.salario_base
                +
                COALESCE(
                    totais.total_proventos,
                    0
                )
                -
                COALESCE(
                    totais.total_descontos,
                    0
                )
            ) AS salario_liquido

        FROM salarios_funcionarios sf

        INNER JOIN empresas e
            ON e.id = sf.empresa_id

        INNER JOIN funcionarios f
            ON f.id = sf.funcionario_id

        LEFT JOIN contratos_trabalho ct
            ON ct.id = sf.contrato_id

        INNER JOIN status st
            ON st.id = sf.status_id

        LEFT JOIN
        (
            SELECT
                sfr.salario_funcionario_id,

                SUM(
                    CASE
                        WHEN UPPER(rs.categoria) = 'PROVENTO'
                        THEN
                            CASE
                                WHEN UPPER(rs.modo_calculo) = 'PERCENTAGEM'
                                THEN (
                                    sf2.salario_base
                                    * COALESCE(
                                        sfr.percentagem,
                                        0
                                    )
                                ) / 100

                                ELSE COALESCE(
                                    sfr.valor,
                                    0
                                )
                            END

                        ELSE 0
                    END
                ) AS total_proventos,

                SUM(
                    CASE
                        WHEN UPPER(rs.categoria) = 'DESCONTO'
                        THEN
                            CASE
                                WHEN UPPER(rs.modo_calculo) = 'PERCENTAGEM'
                                THEN (
                                    sf2.salario_base
                                    * COALESCE(
                                        sfr.percentagem,
                                        0
                                    )
                                ) / 100

                                ELSE COALESCE(
                                    sfr.valor,
                                    0
                                )
                            END

                        ELSE 0
                    END
                ) AS total_descontos

            FROM salario_funcionario_rubricas sfr

            INNER JOIN rubricas_salariais rs
                ON rs.id = sfr.rubrica_id

            INNER JOIN salarios_funcionarios sf2
                ON sf2.id =
                    sfr.salario_funcionario_id

            INNER JOIN status str
                ON str.id = sfr.status_id

            WHERE str.nome = 'Activo'

            GROUP BY
                sfr.salario_funcionario_id

        ) totais
            ON totais.salario_funcionario_id =
                sf.id

        WHERE 1 = 1
    ";

        $params = [];

        if (!empty($filtros['empresa_id'])) {

            $sql .= "
            AND sf.empresa_id = :empresa
        ";

            $params[':empresa'] =
                (int)$filtros['empresa_id'];
        }

        if (!empty($filtros['funcionario'])) {

            $sql .= "
            AND f.nome_completo
                LIKE :funcionario
        ";

            $params[':funcionario'] =
                '%' .
                trim(
                    (string)$filtros['funcionario']
                ) .
                '%';
        }

        if (!empty($filtros['estado'])) {

            $sql .= "
            AND st.nome = :estado
        ";

            $params[':estado'] =
                trim(
                    (string)$filtros['estado']
                );
        }

        $sql .= "
        ORDER BY
            f.nome_completo ASC
    ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute($params);

        return $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );
    }

    /**
     * Busca um salário
     */
    public function buscar(int $id): ?array
    {
        $sql = "

            SELECT *

            FROM salarios_funcionarios

            WHERE id=:id

            LIMIT 1

        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':id' => $id
        ]);

        $salario = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$salario) {

            return null;
        }

        $salario['rubricas'] =
            $this->listarRubricas($id);

        return $salario;
    }

    /**
     * Rubricas do funcionário
     */
    public function listarRubricas(int $salarioId): array
    {
        $sql = "

            SELECT

                sfr.id,

                rs.id AS rubrica_id,

                rs.codigo,

                rs.nome,

                rs.tipo,

                rs.categoria,

                rs.modo_calculo,

                sfr.valor,

                sfr.percentagem,

                sfr.quantidade_padrao,

                sfr.data_inicio,

                sfr.data_fim,

                st.nome estado

            FROM salario_funcionario_rubricas sfr

            INNER JOIN rubricas_salariais rs

                ON rs.id=sfr.rubrica_id

            INNER JOIN status st

                ON st.id=sfr.status_id

            WHERE

                sfr.salario_funcionario_id=:id

            ORDER BY

                rs.nome

        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':id' => $salarioId
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Resumo salarial
     */
    public function resumo(int $salarioId): array
    {
        $salario = $this->buscar($salarioId);

        if (!$salario) {

            throw new Exception(
                'Configuração salarial não encontrada.'
            );
        }

        $base = (float)$salario['salario_base'];

        $proventos = 0;

        $descontos = 0;

        foreach ($salario['rubricas'] as $rubrica) {

            $valor = 0;

            switch ($rubrica['modo_calculo']) {

                case 'VALOR':

                    $valor =
                        (float)$rubrica['valor'];

                    break;

                case 'PERCENTAGEM':

                    $valor =
                        ($base *
                            (float)$rubrica['percentagem'])
                        / 100;

                    break;

                default:

                    $valor =
                        (float)$rubrica['valor'];
            }

            if ($rubrica['categoria'] == 'PROVENTO') {

                $proventos += $valor;
            } else {

                $descontos += $valor;
            }
        }

        return [

            'salario_base' => $base,

            'total_proventos' => $proventos,

            'total_descontos' => $descontos,

            'salario_bruto' => $base + $proventos,

            'salario_liquido' => ($base + $proventos) - $descontos

        ];
    }


    public function buscarContratoActivoFuncionario(
        int $empresaId,
        int $funcionarioId
    ): ?array {

        if ($empresaId <= 0) {
            throw new RuntimeException(
                'Empresa inválida.'
            );
        }

        if ($funcionarioId <= 0) {
            throw new RuntimeException(
                'Funcionário inválido.'
            );
        }

        $sql = "
        SELECT
            contrato.id,
            contrato.numero_contrato,
            contrato.funcionario_id,
            contrato.salario_base,
            contrato.moeda,
            contrato.periodicidade_salario,
            contrato.horas_semanais,
            contrato.data_inicio,
            contrato.data_fim,
            contrato.status_id,

            status.nome AS estado

        FROM contratos_trabalho contrato

        INNER JOIN status
            ON status.id =
                contrato.status_id

           AND status.tipo =
                'contrato_trabalho'

        WHERE contrato.empresa_id =
                :empresa_id

          AND contrato.funcionario_id =
                :funcionario_id

          AND LOWER(status.nome)
                IN
                (
                    'activo',
                    'ativo'
                )

          AND contrato.data_inicio <= CURDATE()

          AND
          (
              contrato.data_fim IS NULL
              OR contrato.data_fim >= CURDATE()
          )

        ORDER BY
            contrato.data_inicio DESC,
            contrato.id DESC

        LIMIT 1
    ";

        $stmt =
            $this->db->prepare(
                $sql
            );

        $stmt->execute([
            ':empresa_id' =>
            $empresaId,

            ':funcionario_id' =>
            $funcionarioId
        ]);

        $contrato =
            $stmt->fetch(
                PDO::FETCH_ASSOC
            );

        return $contrato ?: null;
    }

    /**
     * Cria uma configuração salarial
     */
    public function criar(array $dados): array
    {
        $this->validarDados($dados);

        $this->validarVigencia(
            (int)$dados['empresa_id'],
            (int)$dados['funcionario_id'],
            $dados['data_inicio'],
            $dados['data_fim'] ?? null
        );

        $statusId = $this->obterStatusId(
            self::STATUS_ATIVO
        );

        try {

            $this->db->beginTransaction();

            $sql = "
            INSERT INTO salarios_funcionarios
            (
                uuid,
                empresa_id,
                funcionario_id,
                contrato_id,
                moeda,
                salario_base,
                periodicidade,
                horas_mensais,
                dias_mensais,
                data_inicio,
                data_fim,
                motivo_alteracao,
                observacoes,
                status_id,
                criado_por
            )
            VALUES
            (
                :uuid,
                :empresa,
                :funcionario,
                :contrato,
                :moeda,
                :salario_base,
                :periodicidade,
                :horas_mensais,
                :dias_mensais,
                :data_inicio,
                :data_fim,
                :motivo,
                :observacoes,
                :status,
                :criado_por
            )
        ";

            $stmt = $this->db->prepare($sql);

            $stmt->execute([

                ':uuid'            => $this->gerarUUID(),
                ':empresa'         => $dados['empresa_id'],
                ':funcionario'     => $dados['funcionario_id'],
                ':contrato'        => $dados['contrato_id'] ?: null,
                ':moeda'           => $dados['moeda'],
                ':salario_base'    => $dados['salario_base'],
                ':periodicidade'   => $dados['periodicidade'],
                ':horas_mensais'   => $dados['horas_mensais'],
                ':dias_mensais'    => $dados['dias_mensais'],
                ':data_inicio'     => $dados['data_inicio'],
                ':data_fim'        => $dados['data_fim'] ?: null,
                ':motivo'          => $dados['motivo_alteracao'] ?: null,
                ':observacoes'     => $dados['observacoes'] ?: null,
                ':status'          => $statusId,
                ':criado_por'      => $dados['utilizador_id']

            ]);

            $salarioId = (int)$this->db->lastInsertId();

            if (
                !empty($dados['rubricas']) &&
                is_array($dados['rubricas'])
            ) {

                $this->guardarRubricas(

                    $salarioId,

                    (int)$dados['empresa_id'],

                    (int)$dados['utilizador_id'],

                    $dados['rubricas']

                );
            }

            $this->db->commit();

            return [

                'success' => true,

                'message' => 'Configuração salarial registada com sucesso.',

                'id' => $salarioId

            ];
        } catch (Exception $e) {

            if ($this->db->inTransaction()) {

                $this->db->rollBack();
            }

            throw $e;
        }
    }

    /**
     * Validação dos dados obrigatórios
     */
    private function validarDados(array $dados): void
    {
        if (empty($dados['empresa_id'])) {
            throw new Exception('Empresa não informada.');
        }

        if (empty($dados['funcionario_id'])) {
            throw new Exception('Funcionário não informado.');
        }

        if (!isset($dados['salario_base'])) {
            throw new Exception('Salário base não informado.');
        }

        if ((float)$dados['salario_base'] < 0) {
            throw new Exception('O salário base deve ser maior ou igual a zero.');
        }

        if (empty($dados['moeda'])) {
            throw new Exception('Moeda não informada.');
        }

        if (empty($dados['periodicidade'])) {
            throw new Exception('Periodicidade não informada.');
        }

        if (empty($dados['data_inicio'])) {
            throw new Exception('Data de início não informada.');
        }

        if (
            !empty($dados['data_fim']) &&
            strtotime($dados['data_fim']) < strtotime($dados['data_inicio'])
        ) {
            throw new Exception('A data final não pode ser inferior à data inicial.');
        }
    }

    /**
     * Valida vigência
     */
    /**
     * Impede períodos salariais sobrepostos para o mesmo funcionário.
     *
     * Um período sem data final é considerado aberto.
     */
    private function validarVigencia(
        int $empresaId,
        int $funcionarioId,
        string $inicio,
        ?string $fim,
        ?int $ignorarId = null
    ): void {
        $statusActivoId = $this->obterStatusId(
            self::STATUS_ATIVO
        );

        /*
     * Dois períodos entram em conflito quando:
     *
     * início_existente <= fim_novo
     * e
     * fim_existente >= início_novo
     *
     * Datas finais nulas são tratadas como períodos abertos.
     */
        $sql = "
        SELECT
            id,
            data_inicio,
            data_fim

        FROM salarios_funcionarios

        WHERE empresa_id = :empresa_id
          AND funcionario_id = :funcionario_id
          AND status_id = :status_id

          AND data_inicio <= COALESCE(
              :data_fim,
              '9999-12-31'
          )

          AND COALESCE(
              data_fim,
              '9999-12-31'
          ) >= :data_inicio
    ";

        $params = [
            ':empresa_id' => $empresaId,
            ':funcionario_id' => $funcionarioId,
            ':status_id' => $statusActivoId,
            ':data_inicio' => $inicio,
            ':data_fim' => $fim
        ];

        if ($ignorarId !== null) {
            $sql .= " AND id <> :ignorar_id";
            $params[':ignorar_id'] = $ignorarId;
        }

        $sql .= " LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        $conflito = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($conflito) {
            $periodoExistente = $conflito['data_inicio'];

            if (!empty($conflito['data_fim'])) {
                $periodoExistente .= ' até ' . $conflito['data_fim'];
            } else {
                $periodoExistente .= ' sem data final';
            }

            throw new Exception(
                'Já existe uma configuração salarial activa ' .
                    'para este funcionário no período de ' .
                    $periodoExistente . '.'
            );
        }
    }

    /**
     * Obtém ID do status
     */
    private function obterStatusId(string $nome): int
    {
        $stmt = $this->db->prepare("
        SELECT id

        FROM status

        WHERE tipo = :tipo

        AND nome = :nome

        LIMIT 1
    ");

        $stmt->execute([

            ':tipo' => self::TIPO_STATUS,

            ':nome' => $nome

        ]);

        $id = $stmt->fetchColumn();

        if (!$id) {

            throw new Exception(
                "Estado {$nome} não configurado."
            );
        }

        return (int)$id;
    }

    /**
     * Gera UUID
     */
    private function gerarUUID(): string
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',

            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),

            mt_rand(0, 0xffff),

            mt_rand(0, 0x0fff) | 0x4000,

            mt_rand(0, 0x3fff) | 0x8000,

            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff)
        );
    }

    /**
     * Guarda rubricas
     */
    /**
     * Guarda as rubricas atribuídas à configuração salarial.
     */
    private function guardarRubricas(
        int $salarioId,
        int $empresaId,
        int $utilizadorId,
        array $rubricas
    ): void {
        if (empty($rubricas)) {
            return;
        }

        $statusId = $this->obterStatusRubricaId(
            self::STATUS_ATIVO
        );

        $verificarRubrica = $this->db->prepare("
        SELECT id
        FROM rubricas_salariais
        WHERE id = :rubrica_id
          AND empresa_id = :empresa_id
        LIMIT 1
    ");

        $sql = "
        INSERT INTO salario_funcionario_rubricas
        (
            uuid,
            empresa_id,
            salario_funcionario_id,
            rubrica_id,
            valor,
            percentagem,
            quantidade_padrao,
            data_inicio,
            data_fim,
            observacoes,
            status_id,
            criado_por
        )
        VALUES
        (
            :uuid,
            :empresa_id,
            :salario_funcionario_id,
            :rubrica_id,
            :valor,
            :percentagem,
            :quantidade_padrao,
            :data_inicio,
            :data_fim,
            :observacoes,
            :status_id,
            :criado_por
        )
    ";

        $stmt = $this->db->prepare($sql);

        $rubricasUtilizadas = [];

        foreach ($rubricas as $indice => $rubrica) {
            $rubricaId = (int)($rubrica['rubrica_id'] ?? 0);

            if ($rubricaId <= 0) {
                throw new Exception(
                    'A rubrica da linha ' . ($indice + 1) .
                        ' não foi informada.'
                );
            }

            if (in_array($rubricaId, $rubricasUtilizadas, true)) {
                throw new Exception(
                    'A mesma rubrica não pode ser adicionada mais de uma vez.'
                );
            }

            $rubricasUtilizadas[] = $rubricaId;

            $verificarRubrica->execute([
                ':rubrica_id' => $rubricaId,
                ':empresa_id' => $empresaId
            ]);

            if (!$verificarRubrica->fetchColumn()) {
                throw new Exception(
                    'A rubrica informada não existe ou não pertence à empresa.'
                );
            }

            $valor = (float)($rubrica['valor'] ?? 0);
            $percentagem = (float)($rubrica['percentagem'] ?? 0);

            $quantidade = isset($rubrica['quantidade_padrao'])
                && $rubrica['quantidade_padrao'] !== ''
                ? (float)$rubrica['quantidade_padrao']
                : null;

            if ($valor < 0) {
                throw new Exception(
                    'O valor da rubrica não pode ser negativo.'
                );
            }

            if ($percentagem < 0 || $percentagem > 100) {
                throw new Exception(
                    'A percentagem da rubrica deve estar entre 0 e 100.'
                );
            }

            if ($quantidade !== null && $quantidade < 0) {
                throw new Exception(
                    'A quantidade da rubrica não pode ser negativa.'
                );
            }

            $dataInicio = !empty($rubrica['data_inicio'])
                ? $rubrica['data_inicio']
                : date('Y-m-d');

            $dataFim = !empty($rubrica['data_fim'])
                ? $rubrica['data_fim']
                : null;

            if (
                $dataFim !== null &&
                strtotime($dataFim) < strtotime($dataInicio)
            ) {
                throw new Exception(
                    'A data final da rubrica não pode ser inferior à data inicial.'
                );
            }

            $observacoes = trim(
                (string)($rubrica['observacoes'] ?? '')
            );

            $stmt->execute([
                ':uuid' => $this->gerarUUID(),
                ':empresa_id' => $empresaId,
                ':salario_funcionario_id' => $salarioId,
                ':rubrica_id' => $rubricaId,
                ':valor' => $valor,
                ':percentagem' => $percentagem,
                ':quantidade_padrao' => $quantidade,
                ':data_inicio' => $dataInicio,
                ':data_fim' => $dataFim,
                ':observacoes' => $observacoes !== ''
                    ? $observacoes
                    : null,
                ':status_id' => $statusId,
                ':criado_por' => $utilizadorId > 0
                    ? $utilizadorId
                    : null
            ]);
        }
    }

    /**
     * Remove todas as rubricas do salário
     */
    private function apagarRubricas(
        int $salarioId,
        ?int $empresaId = null
    ): void {
        $sql = "
        DELETE FROM salario_funcionario_rubricas
        WHERE salario_funcionario_id = :salario_id
    ";

        $params = [
            ':salario_id' => $salarioId
        ];

        if ($empresaId !== null) {
            $sql .= " AND empresa_id = :empresa_id";
            $params[':empresa_id'] = $empresaId;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
    }

    /**
     * Atualiza uma configuração salarial e as suas rubricas.
     *
     * @throws Exception
     */
    public function atualizar(int $id, array $dados): array
    {
        if ($id <= 0) {
            throw new Exception('Configuração salarial inválida.');
        }

        $salarioAtual = $this->buscar($id);

        if (!$salarioAtual) {
            throw new Exception('Configuração salarial não encontrada.');
        }

        $this->validarDados($dados);

        $empresaId = (int)($dados['empresa_id'] ?? 0);
        $funcionarioId = (int)($dados['funcionario_id'] ?? 0);
        $utilizadorId = (int)($dados['utilizador_id'] ?? 0);

        /*
     * Impede que seja alterado um registo pertencente
     * a outra empresa.
     */
        if ((int)$salarioAtual['empresa_id'] !== $empresaId) {
            throw new Exception(
                'Esta configuração salarial não pertence à empresa informada.'
            );
        }

        $this->validarVigencia(
            $empresaId,
            $funcionarioId,
            (string)$dados['data_inicio'],
            !empty($dados['data_fim'])
                ? (string)$dados['data_fim']
                : null,
            $id
        );

        $contratoId = !empty($dados['contrato_id'])
            ? (int)$dados['contrato_id']
            : null;

        $dataFim = !empty($dados['data_fim'])
            ? $dados['data_fim']
            : null;

        $motivoAlteracao = trim(
            (string)($dados['motivo_alteracao'] ?? '')
        );

        $observacoes = trim(
            (string)($dados['observacoes'] ?? '')
        );

        try {
            $this->db->beginTransaction();

            $sql = "
            UPDATE salarios_funcionarios
            SET
                funcionario_id = :funcionario_id,
                contrato_id = :contrato_id,
                moeda = :moeda,
                salario_base = :salario_base,
                periodicidade = :periodicidade,
                horas_mensais = :horas_mensais,
                dias_mensais = :dias_mensais,
                data_inicio = :data_inicio,
                data_fim = :data_fim,
                motivo_alteracao = :motivo_alteracao,
                observacoes = :observacoes,
                atualizado_por = :atualizado_por
            WHERE id = :id
              AND empresa_id = :empresa_id
            LIMIT 1
        ";

            $stmt = $this->db->prepare($sql);

            $stmt->execute([
                ':funcionario_id' => $funcionarioId,
                ':contrato_id' => $contratoId,
                ':moeda' => strtoupper(
                    trim((string)$dados['moeda'])
                ),
                ':salario_base' => (float)$dados['salario_base'],
                ':periodicidade' => strtoupper(
                    trim((string)$dados['periodicidade'])
                ),
                ':horas_mensais' => isset($dados['horas_mensais'])
                    && $dados['horas_mensais'] !== ''
                    ? (float)$dados['horas_mensais']
                    : null,
                ':dias_mensais' => isset($dados['dias_mensais'])
                    && $dados['dias_mensais'] !== ''
                    ? (float)$dados['dias_mensais']
                    : null,
                ':data_inicio' => $dados['data_inicio'],
                ':data_fim' => $dataFim,
                ':motivo_alteracao' => $motivoAlteracao !== ''
                    ? $motivoAlteracao
                    : null,
                ':observacoes' => $observacoes !== ''
                    ? $observacoes
                    : null,
                ':atualizado_por' => $utilizadorId > 0
                    ? $utilizadorId
                    : null,
                ':id' => $id,
                ':empresa_id' => $empresaId
            ]);

            /*
         * Na edição, substituímos as associações anteriores
         * pelas rubricas recebidas no formulário.
         */
            $this->apagarRubricas(
                $id,
                $empresaId
            );

            $rubricas = $dados['rubricas'] ?? [];

            if (!is_array($rubricas)) {
                throw new Exception(
                    'A lista de rubricas enviada é inválida.'
                );
            }

            if (!empty($rubricas)) {
                $this->guardarRubricas(
                    $id,
                    $empresaId,
                    $utilizadorId,
                    $rubricas
                );
            }

            $this->db->commit();

            return [
                'success' => true,
                'message' => 'Configuração salarial atualizada com sucesso.',
                'id' => $id
            ];
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            throw $e;
        }
    }

    /**
     * Altera o estado de uma configuração salarial.
     *
     * Estados permitidos:
     * - Activo
     * - Inactivo
     *
     * @throws Exception
     */
    public function alterarEstado(
        int $id,
        int $empresaId,
        string $estado,
        int $utilizadorId
    ): array {
        if ($id <= 0) {
            throw new Exception('Configuração salarial inválida.');
        }

        if ($empresaId <= 0) {
            throw new Exception('Empresa não informada.');
        }

        $estado = trim($estado);

        $estadosPermitidos = [
            self::STATUS_ATIVO,
            self::STATUS_INATIVO
        ];

        if (!in_array($estado, $estadosPermitidos, true)) {
            throw new Exception(
                'Estado inválido. Utilize Activo ou Inactivo.'
            );
        }

        $salario = $this->buscarPorEmpresa($id, $empresaId);

        if (!$salario) {
            throw new Exception(
                'Configuração salarial não encontrada.'
            );
        }

        if ($salario['estado'] === $estado) {
            return [
                'success' => true,
                'message' => "A configuração salarial já está no estado {$estado}.",
                'id' => $id
            ];
        }

        /*
     * Ao ativar novamente, verificamos se a vigência
     * entra em conflito com outra configuração ativa.
     */
        if ($estado === self::STATUS_ATIVO) {
            $this->validarVigencia(
                $empresaId,
                (int)$salario['funcionario_id'],
                (string)$salario['data_inicio'],
                !empty($salario['data_fim'])
                    ? (string)$salario['data_fim']
                    : null,
                $id
            );
        }

        $statusId = $this->obterStatusId($estado);

        try {
            $this->db->beginTransaction();

            $stmt = $this->db->prepare("
            UPDATE salarios_funcionarios
            SET
                status_id = :status_id,
                atualizado_por = :atualizado_por
            WHERE id = :id
              AND empresa_id = :empresa_id
            LIMIT 1
        ");

            $stmt->execute([
                ':status_id' => $statusId,
                ':atualizado_por' => $utilizadorId > 0
                    ? $utilizadorId
                    : null,
                ':id' => $id,
                ':empresa_id' => $empresaId
            ]);

            if ($stmt->rowCount() === 0) {
                throw new Exception(
                    'Não foi possível alterar o estado da configuração salarial.'
                );
            }

            /*
         * Mantém as rubricas sincronizadas com o salário.
         */
            $statusRubricaId = $this->obterStatusRubricaId($estado);

            $stmtRubricas = $this->db->prepare("
            UPDATE salario_funcionario_rubricas
            SET
                status_id = :status_id,
                atualizado_por = :atualizado_por
            WHERE salario_funcionario_id = :salario_id
              AND empresa_id = :empresa_id
        ");

            $stmtRubricas->execute([
                ':status_id' => $statusRubricaId,
                ':atualizado_por' => $utilizadorId > 0
                    ? $utilizadorId
                    : null,
                ':salario_id' => $id,
                ':empresa_id' => $empresaId
            ]);

            $this->db->commit();

            return [
                'success' => true,
                'message' => "Configuração salarial alterada para {$estado}.",
                'id' => $id,
                'estado' => $estado
            ];
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            throw $e;
        }
    }

    /**
     * Busca uma configuração salarial dentro da empresa.
     */
    private function buscarPorEmpresa(
        int $id,
        int $empresaId
    ): ?array {
        $stmt = $this->db->prepare("
        SELECT
            sf.*,
            st.nome AS estado
        FROM salarios_funcionarios sf

        INNER JOIN status st
            ON st.id = sf.status_id

        WHERE sf.id = :id
          AND sf.empresa_id = :empresa_id

        LIMIT 1
    ");

        $stmt->execute([
            ':id' => $id,
            ':empresa_id' => $empresaId
        ]);

        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

        return $resultado ?: null;
    }

    /**
     * Obtém o estado das rubricas atribuídas ao funcionário.
     */
    private function obterStatusRubricaId(string $nome): int
    {
        $stmt = $this->db->prepare("
        SELECT id
        FROM status
        WHERE tipo = :tipo
          AND nome = :nome
        LIMIT 1
    ");

        $stmt->execute([
            ':tipo' => 'salario_funcionario_rubrica',
            ':nome' => $nome
        ]);

        $statusId = $stmt->fetchColumn();

        if (!$statusId) {
            throw new Exception(
                "O estado {$nome} não está configurado para as rubricas do funcionário."
            );
        }

        return (int)$statusId;
    }
}
