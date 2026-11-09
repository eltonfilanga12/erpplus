<?php

declare(strict_types=1);

require_once __DIR__
    . '/../services/AuditoriaService.php';

require_once __DIR__
    . '/../services/LogService.php';


final class TipoContrato
{
    private PDO $pdo;

    private AuditoriaService $auditoria;

    private LogService $log;


    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;

        $this->pdo->setAttribute(
            PDO::ATTR_ERRMODE,
            PDO::ERRMODE_EXCEPTION
        );

        $this->auditoria =
            new AuditoriaService(
                $pdo
            );

        $this->log =
            new LogService(
                $pdo
            );
    }

    // ==================================================
    // LISTAR
    // ==================================================

    public function listar(
        int $empresaId
    ): array {

        $this->validarEmpresa(
            $empresaId
        );

        $stmt = $this->pdo->prepare("
            SELECT
                tipo.id,
                tipo.uuid,
                tipo.empresa_id,
                tipo.codigo,
                tipo.nome,
                tipo.descricao,
                tipo.duracao_determinada,
                tipo.duracao_meses,
                tipo.periodo_experimental_dias,
                tipo.renovacao_automatica,
                tipo.numero_maximo_renovacoes,
                tipo.exige_data_fim,
                tipo.permite_tempo_parcial,
                tipo.permite_horas_extras,
                tipo.gera_ferias,
                tipo.gera_subsidio_ferias,
                tipo.gera_subsidio_natal,
                tipo.sujeito_inss,
                tipo.sujeito_irps,
                tipo.aviso_previo_dias,
                tipo.status_id,
                tipo.criado_por,
                tipo.atualizado_por,
                tipo.criado_em,
                tipo.atualizado_em,

                status.nome AS status_nome,
                status.descricao AS status_descricao

            FROM tipos_contratos tipo

            INNER JOIN status
                ON status.id =
                    tipo.status_id

               AND status.tipo =
                    'tipo_contrato'

            WHERE tipo.empresa_id =
                :empresa_id

            ORDER BY
                tipo.nome ASC
        ");

        $stmt->execute([
            ':empresa_id' =>
            $empresaId
        ]);

        return $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );
    }

    // ==================================================
    // BUSCAR
    // ==================================================

    public function buscar(
        int $empresaId,
        int $tipoContratoId
    ): array {

        $this->validarEmpresa(
            $empresaId
        );

        $this->validarId(
            $tipoContratoId
        );

        $stmt = $this->pdo->prepare("
            SELECT
                tipo.*,

                status.nome AS status_nome,
                status.descricao AS status_descricao

            FROM tipos_contratos tipo

            INNER JOIN status
                ON status.id =
                    tipo.status_id

               AND status.tipo =
                    'tipo_contrato'

            WHERE tipo.id =
                :tipo_contrato_id

              AND tipo.empresa_id =
                :empresa_id

            LIMIT 1
        ");

        $stmt->execute([
            ':tipo_contrato_id' =>
            $tipoContratoId,

            ':empresa_id' =>
            $empresaId
        ]);

        $tipoContrato =
            $stmt->fetch(
                PDO::FETCH_ASSOC
            );

        if (!$tipoContrato) {
            throw new RuntimeException(
                'Tipo de contrato não encontrado.'
            );
        }

        return $tipoContrato;
    }

    // ==================================================
    // CRIAR
    // ==================================================

    public function criar(
        int $empresaId,
        int $usuarioId,
        array $dados
    ): int {

        $this->validarEmpresa(
            $empresaId
        );

        $this->validarUsuario(
            $usuarioId
        );

        $dados =
            $this->normalizarDados(
                $dados
            );

        $this->validarCodigoDuplicado(
            $empresaId,
            $dados['codigo']
        );

        $this->validarNomeDuplicado(
            $empresaId,
            $dados['nome']
        );

        $this->validarStatus(
            $dados['status_id']
        );


        $this->pdo->beginTransaction();


        try {

            $stmt =
                $this->pdo->prepare("
                INSERT INTO tipos_contratos
                (
                    uuid,
                    empresa_id,
                    codigo,
                    nome,
                    descricao,
                    duracao_determinada,
                    duracao_meses,
                    periodo_experimental_dias,
                    renovacao_automatica,
                    numero_maximo_renovacoes,
                    exige_data_fim,
                    permite_tempo_parcial,
                    permite_horas_extras,
                    gera_ferias,
                    gera_subsidio_ferias,
                    gera_subsidio_natal,
                    sujeito_inss,
                    sujeito_irps,
                    aviso_previo_dias,
                    status_id,
                    criado_por,
                    atualizado_por
                )
                VALUES
                (
                    UUID(),
                    :empresa_id,
                    :codigo,
                    :nome,
                    :descricao,
                    :duracao_determinada,
                    :duracao_meses,
                    :periodo_experimental_dias,
                    :renovacao_automatica,
                    :numero_maximo_renovacoes,
                    :exige_data_fim,
                    :permite_tempo_parcial,
                    :permite_horas_extras,
                    :gera_ferias,
                    :gera_subsidio_ferias,
                    :gera_subsidio_natal,
                    :sujeito_inss,
                    :sujeito_irps,
                    :aviso_previo_dias,
                    :status_id,
                    :criado_por,
                    :atualizado_por
                )
            ");


            $stmt->execute([
                ':empresa_id' =>
                $empresaId,

                ':codigo' =>
                $dados['codigo'],

                ':nome' =>
                $dados['nome'],

                ':descricao' =>
                $dados['descricao'],

                ':duracao_determinada' =>
                $dados['duracao_determinada'],

                ':duracao_meses' =>
                $dados['duracao_meses'],

                ':periodo_experimental_dias' =>
                $dados['periodo_experimental_dias'],

                ':renovacao_automatica' =>
                $dados['renovacao_automatica'],

                ':numero_maximo_renovacoes' =>
                $dados['numero_maximo_renovacoes'],

                ':exige_data_fim' =>
                $dados['exige_data_fim'],

                ':permite_tempo_parcial' =>
                $dados['permite_tempo_parcial'],

                ':permite_horas_extras' =>
                $dados['permite_horas_extras'],

                ':gera_ferias' =>
                $dados['gera_ferias'],

                ':gera_subsidio_ferias' =>
                $dados['gera_subsidio_ferias'],

                ':gera_subsidio_natal' =>
                $dados['gera_subsidio_natal'],

                ':sujeito_inss' =>
                $dados['sujeito_inss'],

                ':sujeito_irps' =>
                $dados['sujeito_irps'],

                ':aviso_previo_dias' =>
                $dados['aviso_previo_dias'],

                ':status_id' =>
                $dados['status_id'],

                ':criado_por' =>
                $usuarioId,

                ':atualizado_por' =>
                $usuarioId
            ]);


            $tipoContratoId =
                (int)$this->pdo
                    ->lastInsertId();


            if ($tipoContratoId <= 0) {
                throw new RuntimeException(
                    'Não foi possível identificar o tipo de contrato criado.'
                );
            }


            $tipoContratoDepois =
                $this->buscar(
                    $empresaId,
                    $tipoContratoId
                );


            $this->auditoria->insercao(
                $empresaId,
                $usuarioId,
                'tipos_contratos',
                $tipoContratoId,
                $tipoContratoDepois
            );


            $this->log->registrar(
                $empresaId,
                $usuarioId,
                'TIPOS_CONTRATOS',
                'CRIAR',
                sprintf(
                    'Criou o tipo de contrato %s - %s.',
                    $tipoContratoDepois['codigo'],
                    $tipoContratoDepois['nome']
                ),
                $tipoContratoId
            );


            $this->pdo->commit();


            return $tipoContratoId;
        } catch (Throwable $e) {

            if (
                $this->pdo->inTransaction()
            ) {
                $this->pdo->rollBack();
            }

            throw $e;
        }
    }

    // ==================================================
    // EDITAR
    // ==================================================

    public function editar(
        int $empresaId,
        int $usuarioId,
        int $tipoContratoId,
        array $dados
    ): void {

        $this->validarEmpresa(
            $empresaId
        );

        $this->validarUsuario(
            $usuarioId
        );

        $this->validarId(
            $tipoContratoId
        );


        $tipoContratoAntes =
            $this->buscar(
                $empresaId,
                $tipoContratoId
            );


        $dados =
            $this->normalizarDados(
                $dados
            );


        $this->validarCodigoDuplicado(
            $empresaId,
            $dados['codigo'],
            $tipoContratoId
        );


        $this->validarNomeDuplicado(
            $empresaId,
            $dados['nome'],
            $tipoContratoId
        );


        $this->validarStatus(
            $dados['status_id']
        );


        $this->pdo->beginTransaction();


        try {

            $stmt =
                $this->pdo->prepare("
                UPDATE tipos_contratos

                SET
                    codigo =
                        :codigo,

                    nome =
                        :nome,

                    descricao =
                        :descricao,

                    duracao_determinada =
                        :duracao_determinada,

                    duracao_meses =
                        :duracao_meses,

                    periodo_experimental_dias =
                        :periodo_experimental_dias,

                    renovacao_automatica =
                        :renovacao_automatica,

                    numero_maximo_renovacoes =
                        :numero_maximo_renovacoes,

                    exige_data_fim =
                        :exige_data_fim,

                    permite_tempo_parcial =
                        :permite_tempo_parcial,

                    permite_horas_extras =
                        :permite_horas_extras,

                    gera_ferias =
                        :gera_ferias,

                    gera_subsidio_ferias =
                        :gera_subsidio_ferias,

                    gera_subsidio_natal =
                        :gera_subsidio_natal,

                    sujeito_inss =
                        :sujeito_inss,

                    sujeito_irps =
                        :sujeito_irps,

                    aviso_previo_dias =
                        :aviso_previo_dias,

                    status_id =
                        :status_id,

                    atualizado_por =
                        :atualizado_por

                WHERE id =
                    :tipo_contrato_id

                  AND empresa_id =
                    :empresa_id
            ");


            $stmt->execute([
                ':codigo' =>
                $dados['codigo'],

                ':nome' =>
                $dados['nome'],

                ':descricao' =>
                $dados['descricao'],

                ':duracao_determinada' =>
                $dados['duracao_determinada'],

                ':duracao_meses' =>
                $dados['duracao_meses'],

                ':periodo_experimental_dias' =>
                $dados['periodo_experimental_dias'],

                ':renovacao_automatica' =>
                $dados['renovacao_automatica'],

                ':numero_maximo_renovacoes' =>
                $dados['numero_maximo_renovacoes'],

                ':exige_data_fim' =>
                $dados['exige_data_fim'],

                ':permite_tempo_parcial' =>
                $dados['permite_tempo_parcial'],

                ':permite_horas_extras' =>
                $dados['permite_horas_extras'],

                ':gera_ferias' =>
                $dados['gera_ferias'],

                ':gera_subsidio_ferias' =>
                $dados['gera_subsidio_ferias'],

                ':gera_subsidio_natal' =>
                $dados['gera_subsidio_natal'],

                ':sujeito_inss' =>
                $dados['sujeito_inss'],

                ':sujeito_irps' =>
                $dados['sujeito_irps'],

                ':aviso_previo_dias' =>
                $dados['aviso_previo_dias'],

                ':status_id' =>
                $dados['status_id'],

                ':atualizado_por' =>
                $usuarioId,

                ':tipo_contrato_id' =>
                $tipoContratoId,

                ':empresa_id' =>
                $empresaId
            ]);


            $tipoContratoDepois =
                $this->buscar(
                    $empresaId,
                    $tipoContratoId
                );


            $this->auditoria->atualizacao(
                $empresaId,
                $usuarioId,
                'tipos_contratos',
                $tipoContratoId,
                $tipoContratoAntes,
                $tipoContratoDepois
            );


            $this->log->registrar(
                $empresaId,
                $usuarioId,
                'TIPOS_CONTRATOS',
                'EDITAR',
                sprintf(
                    'Atualizou o tipo de contrato %s - %s.',
                    $tipoContratoDepois['codigo'],
                    $tipoContratoDepois['nome']
                ),
                $tipoContratoId
            );


            $this->pdo->commit();
        } catch (Throwable $e) {

            if (
                $this->pdo->inTransaction()
            ) {
                $this->pdo->rollBack();
            }

            throw $e;
        }
    }

    // ==================================================
    // ELIMINAR
    // ==================================================

    public function eliminar(
        int $empresaId,
        int $usuarioId,
        int $tipoContratoId
    ): void {

        $this->validarEmpresa(
            $empresaId
        );

        $this->validarUsuario(
            $usuarioId
        );

        $this->validarId(
            $tipoContratoId
        );


        $tipoContrato =
            $this->buscar(
                $empresaId,
                $tipoContratoId
            );


        /*
     * Quando a tabela contratos_trabalho existir,
     * manter aqui a validação para impedir
     * eliminar tipos de contrato já utilizados.
     */


        $this->pdo->beginTransaction();


        try {

            $stmt =
                $this->pdo->prepare("
                DELETE FROM tipos_contratos

                WHERE id =
                    :tipo_contrato_id

                  AND empresa_id =
                    :empresa_id
            ");


            $stmt->execute([
                ':tipo_contrato_id' =>
                $tipoContratoId,

                ':empresa_id' =>
                $empresaId
            ]);


            if (
                $stmt->rowCount()
                !== 1
            ) {
                throw new RuntimeException(
                    'Não foi possível eliminar o tipo de contrato.'
                );
            }


            $this->auditoria->exclusao(
                $empresaId,
                $usuarioId,
                'tipos_contratos',
                $tipoContratoId,
                $tipoContrato
            );


            $this->log->registrar(
                $empresaId,
                $usuarioId,
                'TIPOS_CONTRATOS',
                'ELIMINAR',
                sprintf(
                    'Eliminou o tipo de contrato %s - %s.',
                    $tipoContrato['codigo'],
                    $tipoContrato['nome']
                ),
                $tipoContratoId
            );


            $this->pdo->commit();
        } catch (Throwable $e) {

            if (
                $this->pdo->inTransaction()
            ) {
                $this->pdo->rollBack();
            }

            throw $e;
        }
    }

    // ==================================================
    // DADOS INICIAIS
    // ==================================================

    public function dadosIniciais(
        int $empresaId
    ): array {

        $this->validarEmpresa(
            $empresaId
        );

        return [
            'tipos_contratos' =>
            $this->listar(
                $empresaId
            ),

            'status' =>
            $this->listarStatus()
        ];
    }

    // ==================================================
    // NORMALIZAR DADOS
    // ==================================================

    private function normalizarDados(
        array $dados
    ): array {

        $codigo =
            strtoupper(
                trim(
                    (string)(
                        $dados['codigo']
                        ?? ''
                    )
                )
            );

        $nome =
            trim(
                (string)(
                    $dados['nome']
                    ?? ''
                )
            );

        $descricao =
            $this->textoOpcional(
                $dados['descricao']
                    ?? null
            );

        $duracaoDeterminada =
            $this->booleano(
                $dados['duracao_determinada']
                    ?? 0
            );

        $duracaoMeses =
            $this->inteiroOpcional(
                $dados['duracao_meses']
                    ?? null
            );

        $periodoExperimentalDias =
            $this->inteiroOpcionalPermitindoZero(
                $dados['periodo_experimental_dias']
                    ?? null
            );

        $renovacaoAutomatica =
            $this->booleano(
                $dados['renovacao_automatica']
                    ?? 0
            );

        $numeroMaximoRenovacoes =
            $this->inteiroOpcionalPermitindoZero(
                $dados['numero_maximo_renovacoes']
                    ?? null
            );

        $exigeDataFim =
            $this->booleano(
                $dados['exige_data_fim']
                    ?? 0
            );

        $permiteTempoParcial =
            $this->booleano(
                $dados['permite_tempo_parcial']
                    ?? 0
            );

        $permiteHorasExtras =
            $this->booleano(
                $dados['permite_horas_extras']
                    ?? 0
            );

        $geraFerias =
            $this->booleano(
                $dados['gera_ferias']
                    ?? 0
            );

        $geraSubsidioFerias =
            $this->booleano(
                $dados['gera_subsidio_ferias']
                    ?? 0
            );

        $geraSubsidioNatal =
            $this->booleano(
                $dados['gera_subsidio_natal']
                    ?? 0
            );

        $sujeitoInss =
            $this->booleano(
                $dados['sujeito_inss']
                    ?? 0
            );

        $sujeitoIrps =
            $this->booleano(
                $dados['sujeito_irps']
                    ?? 0
            );

        $avisoPrevioDias =
            $this->inteiroOpcionalPermitindoZero(
                $dados['aviso_previo_dias']
                    ?? null
            );

        $statusId =
            (int)(
                $dados['status_id']
                ?? 0
            );

        if ($codigo === '') {
            throw new RuntimeException(
                'Informe o código do tipo de contrato.'
            );
        }

        if (mb_strlen($codigo) > 20) {
            throw new RuntimeException(
                'O código do tipo de contrato não pode exceder 20 caracteres.'
            );
        }

        if ($nome === '') {
            throw new RuntimeException(
                'Informe o nome do tipo de contrato.'
            );
        }

        if (mb_strlen($nome) > 120) {
            throw new RuntimeException(
                'O nome do tipo de contrato não pode exceder 120 caracteres.'
            );
        }

        if ($duracaoDeterminada === 1) {

            if (
                $duracaoMeses === null
                || $duracaoMeses <= 0
            ) {
                throw new RuntimeException(
                    'Informe a duração do contrato em meses.'
                );
            }

            $exigeDataFim = 1;
        } else {

            $duracaoMeses = null;

            $renovacaoAutomatica = 0;

            $numeroMaximoRenovacoes = null;
        }

        if (
            $renovacaoAutomatica === 1
            && $duracaoDeterminada !== 1
        ) {
            throw new RuntimeException(
                'A renovação automática só pode ser usada em contratos de duração determinada.'
            );
        }

        if (
            $renovacaoAutomatica === 1
            && $numeroMaximoRenovacoes === null
        ) {
            throw new RuntimeException(
                'Informe o número máximo de renovações.'
            );
        }

        if (
            $renovacaoAutomatica === 0
        ) {
            $numeroMaximoRenovacoes = null;
        }

        if (
            $periodoExperimentalDias !== null
            && $periodoExperimentalDias > 3650
        ) {
            throw new RuntimeException(
                'O período experimental informado é inválido.'
            );
        }

        if (
            $numeroMaximoRenovacoes !== null
            && $numeroMaximoRenovacoes > 255
        ) {
            throw new RuntimeException(
                'O número máximo de renovações é inválido.'
            );
        }

        if (
            $avisoPrevioDias !== null
            && $avisoPrevioDias > 3650
        ) {
            throw new RuntimeException(
                'O período de aviso prévio informado é inválido.'
            );
        }

        if ($statusId <= 0) {
            $statusId =
                $this->statusAtivoId();
        }

        return [
            'codigo' =>
            $codigo,

            'nome' =>
            $nome,

            'descricao' =>
            $descricao,

            'duracao_determinada' =>
            $duracaoDeterminada,

            'duracao_meses' =>
            $duracaoMeses,

            'periodo_experimental_dias' =>
            $periodoExperimentalDias,

            'renovacao_automatica' =>
            $renovacaoAutomatica,

            'numero_maximo_renovacoes' =>
            $numeroMaximoRenovacoes,

            'exige_data_fim' =>
            $exigeDataFim,

            'permite_tempo_parcial' =>
            $permiteTempoParcial,

            'permite_horas_extras' =>
            $permiteHorasExtras,

            'gera_ferias' =>
            $geraFerias,

            'gera_subsidio_ferias' =>
            $geraSubsidioFerias,

            'gera_subsidio_natal' =>
            $geraSubsidioNatal,

            'sujeito_inss' =>
            $sujeitoInss,

            'sujeito_irps' =>
            $sujeitoIrps,

            'aviso_previo_dias' =>
            $avisoPrevioDias,

            'status_id' =>
            $statusId
        ];
    }

    // ==================================================
    // DUPLICADOS
    // ==================================================

    private function validarCodigoDuplicado(
        int $empresaId,
        string $codigo,
        ?int $ignorarId = null
    ): void {

        $sql = "
            SELECT id

            FROM tipos_contratos

            WHERE empresa_id =
                :empresa_id

              AND codigo =
                :codigo
        ";

        $params = [
            ':empresa_id' =>
            $empresaId,

            ':codigo' =>
            $codigo
        ];

        if ($ignorarId !== null) {

            $sql .= "
                AND id <> :ignorar_id
            ";

            $params[':ignorar_id'] =
                $ignorarId;
        }

        $sql .= " LIMIT 1";

        $stmt =
            $this->pdo->prepare(
                $sql
            );

        $stmt->execute(
            $params
        );

        if ($stmt->fetchColumn()) {
            throw new RuntimeException(
                'Já existe um tipo de contrato com este código.'
            );
        }
    }

    private function validarNomeDuplicado(
        int $empresaId,
        string $nome,
        ?int $ignorarId = null
    ): void {

        $sql = "
            SELECT id

            FROM tipos_contratos

            WHERE empresa_id =
                :empresa_id

              AND LOWER(nome) =
                LOWER(:nome)
        ";

        $params = [
            ':empresa_id' =>
            $empresaId,

            ':nome' =>
            $nome
        ];

        if ($ignorarId !== null) {

            $sql .= "
                AND id <> :ignorar_id
            ";

            $params[':ignorar_id'] =
                $ignorarId;
        }

        $sql .= " LIMIT 1";

        $stmt =
            $this->pdo->prepare(
                $sql
            );

        $stmt->execute(
            $params
        );

        if ($stmt->fetchColumn()) {
            throw new RuntimeException(
                'Já existe um tipo de contrato com este nome.'
            );
        }
    }

    // ==================================================
    // STATUS
    // ==================================================

    private function listarStatus(): array
    {
        $stmt = $this->pdo->query("
            SELECT
                id,
                nome,
                descricao

            FROM status

            WHERE tipo =
                'tipo_contrato'

            ORDER BY id ASC
        ");

        return $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );
    }

    private function validarStatus(
        int $statusId
    ): void {

        $stmt = $this->pdo->prepare("
            SELECT id

            FROM status

            WHERE id =
                :status_id

              AND tipo =
                'tipo_contrato'

            LIMIT 1
        ");

        $stmt->execute([
            ':status_id' =>
            $statusId
        ]);

        if (!$stmt->fetchColumn()) {
            throw new RuntimeException(
                'Estado do tipo de contrato inválido.'
            );
        }
    }

    private function statusAtivoId(): int
    {
        $stmt = $this->pdo->query("
            SELECT id

            FROM status

            WHERE tipo =
                'tipo_contrato'

              AND LOWER(nome) IN
              (
                  'activo',
                  'ativo',
                  'activa',
                  'ativa'
              )

            LIMIT 1
        ");

        $id =
            (int)$stmt->fetchColumn();

        if ($id <= 0) {
            throw new RuntimeException(
                'O estado ativo do tipo de contrato não está configurado.'
            );
        }

        return $id;
    }

    // ==================================================
    // VALIDAÇÕES GERAIS
    // ==================================================

    private function validarUsuario(
        int $usuarioId
    ): void {

        if ($usuarioId <= 0) {
            throw new RuntimeException(
                'Utilizador inválido.'
            );
        }

        $stmt = $this->pdo->prepare("
            SELECT id

            FROM usuarios

            WHERE id =
                :usuario_id

            LIMIT 1
        ");

        $stmt->execute([
            ':usuario_id' =>
            $usuarioId
        ]);

        if (!$stmt->fetchColumn()) {
            throw new RuntimeException(
                'Utilizador não encontrado.'
            );
        }
    }

    private function validarEmpresa(
        int $empresaId
    ): void {

        if ($empresaId <= 0) {
            throw new RuntimeException(
                'Empresa inválida para a gestão dos tipos de contrato.'
            );
        }
    }

    private function validarId(
        int $tipoContratoId
    ): void {

        if ($tipoContratoId <= 0) {
            throw new RuntimeException(
                'Tipo de contrato inválido.'
            );
        }
    }

    // ==================================================
    // HELPERS
    // ==================================================

    private function inteiroOpcional(
        mixed $valor
    ): ?int {

        if (
            $valor === null
            || $valor === ''
        ) {
            return null;
        }

        $inteiro =
            filter_var(
                $valor,
                FILTER_VALIDATE_INT
            );

        if (
            $inteiro === false
            || $inteiro <= 0
        ) {
            return null;
        }

        return (int)$inteiro;
    }

    private function inteiroOpcionalPermitindoZero(
        mixed $valor
    ): ?int {

        if (
            $valor === null
            || $valor === ''
        ) {
            return null;
        }

        $inteiro =
            filter_var(
                $valor,
                FILTER_VALIDATE_INT
            );

        if (
            $inteiro === false
            || $inteiro < 0
        ) {
            throw new RuntimeException(
                'Foi informado um número inteiro inválido.'
            );
        }

        return (int)$inteiro;
    }

    private function textoOpcional(
        mixed $valor
    ): ?string {

        $texto =
            trim(
                (string)(
                    $valor ?? ''
                )
            );

        return $texto !== ''
            ? $texto
            : null;
    }

    private function booleano(
        mixed $valor
    ): int {

        if (is_bool($valor)) {
            return $valor
                ? 1
                : 0;
        }

        if (is_int($valor)) {
            return $valor === 1
                ? 1
                : 0;
        }

        $texto =
            strtolower(
                trim(
                    (string)$valor
                )
            );

        return in_array(
            $texto,
            [
                '1',
                'true',
                'sim',
                'yes',
                'on'
            ],
            true
        )
            ? 1
            : 0;
    }
}
