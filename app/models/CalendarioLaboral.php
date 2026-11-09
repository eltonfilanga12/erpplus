<?php

declare(strict_types=1);

final class CalendarioLaboral
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    // ==================================================
    // LISTAR
    // ==================================================

    public function listar(
        int $empresaId,
        array $filtros = []
    ): array {

        $this->validarEmpresa(
            $empresaId
        );

        $dataInicio =
            $this->dataOpcional(
                $filtros['data_inicio']
                    ?? null
            );

        $dataFim =
            $this->dataOpcional(
                $filtros['data_fim']
                    ?? null
            );

        $filialId =
            $this->inteiroOpcional(
                $filtros['filial_id']
                    ?? null
            );

        $tipoEvento =
            $this->tipoEventoOpcional(
                $filtros['tipo_evento']
                    ?? null
            );

        $statusId =
            $this->inteiroOpcional(
                $filtros['status_id']
                    ?? null
            );

        if (
            $dataInicio !== null
            && $dataFim !== null
            && $dataFim < $dataInicio
        ) {
            throw new RuntimeException(
                'A data final do filtro não pode ser anterior à data inicial.'
            );
        }

        $sql = "
            SELECT
                calendario.id,
                calendario.uuid,
                calendario.empresa_id,
                calendario.filial_id,
                calendario.codigo,
                calendario.titulo,
                calendario.descricao,
                calendario.tipo_evento,
                calendario.data_inicio,
                calendario.data_fim,
                calendario.hora_inicio,
                calendario.hora_fim,
                calendario.dia_inteiro,
                calendario.dia_util,
                calendario.desconta_ferias,
                calendario.remunerado,
                calendario.permite_trabalho,
                calendario.exige_compensacao,
                calendario.recorrente_anual,
                calendario.aplica_todos_funcionarios,
                calendario.cor,
                calendario.observacoes,
                calendario.status_id,
                calendario.criado_por,
                calendario.atualizado_por,
                calendario.criado_em,
                calendario.atualizado_em,

                filial.nome AS filial_nome,
                filial.codigo AS filial_codigo,

                status.nome AS status_nome,
                status.descricao AS status_descricao

            FROM calendario_laboral calendario

            LEFT JOIN filiais filial
                ON filial.id =
                    calendario.filial_id

               AND filial.empresa_id =
                    calendario.empresa_id

            INNER JOIN status
                ON status.id =
                    calendario.status_id

               AND status.tipo =
                    'calendario_laboral'

            WHERE calendario.empresa_id =
                :empresa_id
        ";

        $params = [
            ':empresa_id' =>
            $empresaId
        ];

        if ($dataInicio !== null) {

            $sql .= "
                AND calendario.data_fim >=
                    :data_inicio
            ";

            $params[':data_inicio'] =
                $dataInicio;
        }

        if ($dataFim !== null) {

            $sql .= "
                AND calendario.data_inicio <=
                    :data_fim
            ";

            $params[':data_fim'] =
                $dataFim;
        }

        if ($filialId !== null) {

            $sql .= "
                AND (
                    calendario.filial_id =
                        :filial_id

                    OR calendario.filial_id
                        IS NULL
                )
            ";

            $params[':filial_id'] =
                $filialId;
        }

        if ($tipoEvento !== null) {

            $sql .= "
                AND calendario.tipo_evento =
                    :tipo_evento
            ";

            $params[':tipo_evento'] =
                $tipoEvento;
        }

        if ($statusId !== null) {

            $sql .= "
                AND calendario.status_id =
                    :status_id
            ";

            $params[':status_id'] =
                $statusId;
        }

        $sql .= "
            ORDER BY
                calendario.data_inicio ASC,
                calendario.hora_inicio ASC,
                calendario.titulo ASC
        ";

        $stmt =
            $this->pdo->prepare(
                $sql
            );

        $stmt->execute(
            $params
        );

        return $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );
    }

    // ==================================================
    // BUSCAR
    // ==================================================

    public function buscar(
        int $empresaId,
        int $eventoId
    ): array {

        $this->validarEmpresa(
            $empresaId
        );

        $this->validarId(
            $eventoId
        );

        $stmt = $this->pdo->prepare("
            SELECT
                calendario.*,

                filial.nome AS filial_nome,
                filial.codigo AS filial_codigo,

                status.nome AS status_nome,
                status.descricao AS status_descricao

            FROM calendario_laboral calendario

            LEFT JOIN filiais filial
                ON filial.id =
                    calendario.filial_id

               AND filial.empresa_id =
                    calendario.empresa_id

            INNER JOIN status
                ON status.id =
                    calendario.status_id

               AND status.tipo =
                    'calendario_laboral'

            WHERE calendario.id =
                :evento_id

              AND calendario.empresa_id =
                :empresa_id

            LIMIT 1
        ");

        $stmt->execute([
            ':evento_id' =>
            $eventoId,

            ':empresa_id' =>
            $empresaId
        ]);

        $evento =
            $stmt->fetch(
                PDO::FETCH_ASSOC
            );

        if (!$evento) {
            throw new RuntimeException(
                'Evento do calendário laboral não encontrado.'
            );
        }

        return $evento;
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

        $this->validarRelacionamentos(
            $empresaId,
            $dados
        );

        $stmt = $this->pdo->prepare("
            INSERT INTO calendario_laboral
            (
                uuid,
                empresa_id,
                filial_id,
                codigo,
                titulo,
                descricao,
                tipo_evento,
                data_inicio,
                data_fim,
                hora_inicio,
                hora_fim,
                dia_inteiro,
                dia_util,
                desconta_ferias,
                remunerado,
                permite_trabalho,
                exige_compensacao,
                recorrente_anual,
                aplica_todos_funcionarios,
                cor,
                observacoes,
                status_id,
                criado_por,
                atualizado_por
            )
            VALUES
            (
                UUID(),
                :empresa_id,
                :filial_id,
                :codigo,
                :titulo,
                :descricao,
                :tipo_evento,
                :data_inicio,
                :data_fim,
                :hora_inicio,
                :hora_fim,
                :dia_inteiro,
                :dia_util,
                :desconta_ferias,
                :remunerado,
                :permite_trabalho,
                :exige_compensacao,
                :recorrente_anual,
                :aplica_todos_funcionarios,
                :cor,
                :observacoes,
                :status_id,
                :criado_por,
                :atualizado_por
            )
        ");

        $stmt->execute([
            ':empresa_id' =>
            $empresaId,

            ':filial_id' =>
            $dados['filial_id'],

            ':codigo' =>
            $dados['codigo'],

            ':titulo' =>
            $dados['titulo'],

            ':descricao' =>
            $dados['descricao'],

            ':tipo_evento' =>
            $dados['tipo_evento'],

            ':data_inicio' =>
            $dados['data_inicio'],

            ':data_fim' =>
            $dados['data_fim'],

            ':hora_inicio' =>
            $dados['hora_inicio'],

            ':hora_fim' =>
            $dados['hora_fim'],

            ':dia_inteiro' =>
            $dados['dia_inteiro'],

            ':dia_util' =>
            $dados['dia_util'],

            ':desconta_ferias' =>
            $dados['desconta_ferias'],

            ':remunerado' =>
            $dados['remunerado'],

            ':permite_trabalho' =>
            $dados['permite_trabalho'],

            ':exige_compensacao' =>
            $dados['exige_compensacao'],

            ':recorrente_anual' =>
            $dados['recorrente_anual'],

            ':aplica_todos_funcionarios' =>
            $dados['aplica_todos_funcionarios'],

            ':cor' =>
            $dados['cor'],

            ':observacoes' =>
            $dados['observacoes'],

            ':status_id' =>
            $dados['status_id'],

            ':criado_por' =>
            $usuarioId,

            ':atualizado_por' =>
            $usuarioId
        ]);

        return (int)$this->pdo
            ->lastInsertId();
    }

    // ==================================================
    // EDITAR
    // ==================================================

    public function editar(
        int $empresaId,
        int $usuarioId,
        int $eventoId,
        array $dados
    ): void {

        $this->validarEmpresa(
            $empresaId
        );

        $this->validarUsuario(
            $usuarioId
        );

        $this->validarId(
            $eventoId
        );

        $this->buscar(
            $empresaId,
            $eventoId
        );

        $dados =
            $this->normalizarDados(
                $dados
            );

        $this->validarCodigoDuplicado(
            $empresaId,
            $dados['codigo'],
            $eventoId
        );

        $this->validarRelacionamentos(
            $empresaId,
            $dados
        );

        $stmt = $this->pdo->prepare("
            UPDATE calendario_laboral

            SET
                filial_id =
                    :filial_id,

                codigo =
                    :codigo,

                titulo =
                    :titulo,

                descricao =
                    :descricao,

                tipo_evento =
                    :tipo_evento,

                data_inicio =
                    :data_inicio,

                data_fim =
                    :data_fim,

                hora_inicio =
                    :hora_inicio,

                hora_fim =
                    :hora_fim,

                dia_inteiro =
                    :dia_inteiro,

                dia_util =
                    :dia_util,

                desconta_ferias =
                    :desconta_ferias,

                remunerado =
                    :remunerado,

                permite_trabalho =
                    :permite_trabalho,

                exige_compensacao =
                    :exige_compensacao,

                recorrente_anual =
                    :recorrente_anual,

                aplica_todos_funcionarios =
                    :aplica_todos_funcionarios,

                cor =
                    :cor,

                observacoes =
                    :observacoes,

                status_id =
                    :status_id,

                atualizado_por =
                    :atualizado_por

            WHERE id =
                :evento_id

              AND empresa_id =
                :empresa_id
        ");

        $stmt->execute([
            ':filial_id' =>
            $dados['filial_id'],

            ':codigo' =>
            $dados['codigo'],

            ':titulo' =>
            $dados['titulo'],

            ':descricao' =>
            $dados['descricao'],

            ':tipo_evento' =>
            $dados['tipo_evento'],

            ':data_inicio' =>
            $dados['data_inicio'],

            ':data_fim' =>
            $dados['data_fim'],

            ':hora_inicio' =>
            $dados['hora_inicio'],

            ':hora_fim' =>
            $dados['hora_fim'],

            ':dia_inteiro' =>
            $dados['dia_inteiro'],

            ':dia_util' =>
            $dados['dia_util'],

            ':desconta_ferias' =>
            $dados['desconta_ferias'],

            ':remunerado' =>
            $dados['remunerado'],

            ':permite_trabalho' =>
            $dados['permite_trabalho'],

            ':exige_compensacao' =>
            $dados['exige_compensacao'],

            ':recorrente_anual' =>
            $dados['recorrente_anual'],

            ':aplica_todos_funcionarios' =>
            $dados['aplica_todos_funcionarios'],

            ':cor' =>
            $dados['cor'],

            ':observacoes' =>
            $dados['observacoes'],

            ':status_id' =>
            $dados['status_id'],

            ':atualizado_por' =>
            $usuarioId,

            ':evento_id' =>
            $eventoId,

            ':empresa_id' =>
            $empresaId
        ]);
    }

    // ==================================================
    // ELIMINAR
    // ==================================================

    public function eliminar(
        int $empresaId,
        int $eventoId
    ): void {

        $this->validarEmpresa(
            $empresaId
        );

        $this->validarId(
            $eventoId
        );

        $this->buscar(
            $empresaId,
            $eventoId
        );

        $stmt = $this->pdo->prepare("
            DELETE FROM calendario_laboral

            WHERE id =
                :evento_id

              AND empresa_id =
                :empresa_id
        ");

        $stmt->execute([
            ':evento_id' =>
            $eventoId,

            ':empresa_id' =>
            $empresaId
        ]);

        if ($stmt->rowCount() !== 1) {
            throw new RuntimeException(
                'Não foi possível eliminar o evento do calendário laboral.'
            );
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
            'eventos' =>
            $this->listar(
                $empresaId
            ),

            'filiais' =>
            $this->listarFiliais(
                $empresaId
            ),

            'status' =>
            $this->listarStatus(),

            'tipos_eventos' =>
            $this->listarTiposEventos()
        ];
    }

    // ==================================================
    // LISTAR FILIAIS
    // ==================================================

    private function listarFiliais(
        int $empresaId
    ): array {

        $stmt = $this->pdo->prepare("
        SELECT
            filial.id,
            filial.codigo,
            filial.nome

        FROM filiais filial

        WHERE filial.empresa_id =
            :empresa_id

        ORDER BY
            filial.nome ASC
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
    // LISTAR STATUS
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
                'calendario_laboral'

            ORDER BY id ASC
        ");

        return $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );
    }

    // ==================================================
    // LISTAR TIPOS DE EVENTOS
    // ==================================================

    private function listarTiposEventos(): array
    {
        return [
            [
                'codigo' =>
                'FERIADO_NACIONAL',

                'nome' =>
                'Feriado Nacional'
            ],
            [
                'codigo' =>
                'FERIADO_LOCAL',

                'nome' =>
                'Feriado Local'
            ],
            [
                'codigo' =>
                'TOLERANCIA_PONTO',

                'nome' =>
                'Tolerância de Ponto'
            ],
            [
                'codigo' =>
                'DIA_NAO_UTIL',

                'nome' =>
                'Dia Não Útil'
            ],
            [
                'codigo' =>
                'DIA_UTIL_ESPECIAL',

                'nome' =>
                'Dia Útil Especial'
            ],
            [
                'codigo' =>
                'EVENTO_INTERNO',

                'nome' =>
                'Evento Interno'
            ],
            [
                'codigo' =>
                'SUSPENSAO_ATIVIDADE',

                'nome' =>
                'Suspensão de Atividade'
            ],
            [
                'codigo' =>
                'OUTRO',

                'nome' =>
                'Outro'
            ]
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

        $titulo =
            trim(
                (string)(
                    $dados['titulo']
                    ?? ''
                )
            );

        $descricao =
            $this->textoOpcional(
                $dados['descricao']
                    ?? null
            );

        $tipoEvento =
            $this->tipoEventoObrigatorio(
                $dados['tipo_evento']
                    ?? null
            );

        $dataInicio =
            $this->dataObrigatoria(
                $dados['data_inicio']
                    ?? null,
                'data inicial'
            );

        $dataFim =
            $this->dataObrigatoria(
                $dados['data_fim']
                    ?? $dataInicio,
                'data final'
            );

        $diaInteiro =
            $this->booleano(
                $dados['dia_inteiro']
                    ?? 1
            );

        $horaInicio =
            $this->horaOpcional(
                $dados['hora_inicio']
                    ?? null,
                'hora inicial'
            );

        $horaFim =
            $this->horaOpcional(
                $dados['hora_fim']
                    ?? null,
                'hora final'
            );

        $filialId =
            $this->inteiroOpcional(
                $dados['filial_id']
                    ?? null
            );

        $diaUtil =
            $this->booleano(
                $dados['dia_util']
                    ?? 0
            );

        $descontaFerias =
            $this->booleano(
                $dados['desconta_ferias']
                    ?? 0
            );

        $remunerado =
            $this->booleano(
                $dados['remunerado']
                    ?? 0
            );

        $permiteTrabalho =
            $this->booleano(
                $dados['permite_trabalho']
                    ?? 0
            );

        $exigeCompensacao =
            $this->booleano(
                $dados['exige_compensacao']
                    ?? 0
            );

        $recorrenteAnual =
            $this->booleano(
                $dados['recorrente_anual']
                    ?? 0
            );

        $aplicaTodosFuncionarios =
            $this->booleano(
                $dados['aplica_todos_funcionarios']
                    ?? 0
            );

        $cor =
            $this->corOpcional(
                $dados['cor']
                    ?? null
            );

        $observacoes =
            $this->textoOpcional(
                $dados['observacoes']
                    ?? null
            );

        $statusId =
            (int)(
                $dados['status_id']
                ?? 0
            );

        if ($codigo === '') {
            throw new RuntimeException(
                'Informe o código do evento.'
            );
        }

        if (mb_strlen($codigo) > 30) {
            throw new RuntimeException(
                'O código do evento não pode exceder 30 caracteres.'
            );
        }

        if (
            !preg_match(
                '/^[A-Z0-9._-]+$/',
                $codigo
            )
        ) {
            throw new RuntimeException(
                'O código do evento contém caracteres inválidos.'
            );
        }

        if ($titulo === '') {
            throw new RuntimeException(
                'Informe o título do evento.'
            );
        }

        if (mb_strlen($titulo) > 150) {
            throw new RuntimeException(
                'O título do evento não pode exceder 150 caracteres.'
            );
        }

        if ($dataFim < $dataInicio) {
            throw new RuntimeException(
                'A data final não pode ser anterior à data inicial.'
            );
        }

        $diasEvento =
            (
                new DateTimeImmutable(
                    $dataInicio
                )
            )->diff(
                new DateTimeImmutable(
                    $dataFim
                )
            )->days;

        if (
            $diasEvento !== false
            && $diasEvento > 3660
        ) {
            throw new RuntimeException(
                'O período do evento é demasiado longo.'
            );
        }

        if ($diaInteiro === 1) {

            $horaInicio = null;
            $horaFim = null;
        } else {

            if (
                $horaInicio === null
                || $horaFim === null
            ) {
                throw new RuntimeException(
                    'Informe a hora inicial e a hora final do evento.'
                );
            }

            if (
                $dataInicio === $dataFim
                && $horaFim <= $horaInicio
            ) {
                throw new RuntimeException(
                    'A hora final deve ser posterior à hora inicial.'
                );
            }
        }

        if (
            $tipoEvento ===
            'DIA_UTIL_ESPECIAL'
        ) {
            $diaUtil = 1;
        }

        if (
            in_array(
                $tipoEvento,
                [
                    'FERIADO_NACIONAL',
                    'FERIADO_LOCAL',
                    'DIA_NAO_UTIL',
                    'SUSPENSAO_ATIVIDADE'
                ],
                true
            )
        ) {
            $diaUtil = 0;
        }

        if (
            $tipoEvento ===
            'FERIADO_NACIONAL'
        ) {
            $filialId = null;

            $aplicaTodosFuncionarios = 1;
        }

        if (
            $exigeCompensacao === 1
            && $permiteTrabalho !== 1
        ) {
            throw new RuntimeException(
                'A compensação só pode ser exigida quando o trabalho for permitido.'
            );
        }

        if ($statusId <= 0) {
            $statusId =
                $this->statusAtivoId();
        }

        return [
            'filial_id' =>
            $filialId,

            'codigo' =>
            $codigo,

            'titulo' =>
            $titulo,

            'descricao' =>
            $descricao,

            'tipo_evento' =>
            $tipoEvento,

            'data_inicio' =>
            $dataInicio,

            'data_fim' =>
            $dataFim,

            'hora_inicio' =>
            $horaInicio,

            'hora_fim' =>
            $horaFim,

            'dia_inteiro' =>
            $diaInteiro,

            'dia_util' =>
            $diaUtil,

            'desconta_ferias' =>
            $descontaFerias,

            'remunerado' =>
            $remunerado,

            'permite_trabalho' =>
            $permiteTrabalho,

            'exige_compensacao' =>
            $exigeCompensacao,

            'recorrente_anual' =>
            $recorrenteAnual,

            'aplica_todos_funcionarios' =>
            $aplicaTodosFuncionarios,

            'cor' =>
            $cor,

            'observacoes' =>
            $observacoes,

            'status_id' =>
            $statusId
        ];
    }

    // ==================================================
    // VALIDAR RELACIONAMENTOS
    // ==================================================

    private function validarRelacionamentos(
        int $empresaId,
        array $dados
    ): void {

        if (
            $dados['filial_id']
            !== null
        ) {
            $this->validarFilial(
                $empresaId,
                $dados['filial_id']
            );
        }

        $this->validarStatus(
            $dados['status_id']
        );
    }

    // ==================================================
    // VALIDAR FILIAL
    // ==================================================

    private function validarFilial(
        int $empresaId,
        int $filialId
    ): void {

        $stmt = $this->pdo->prepare("
            SELECT id

            FROM filiais

            WHERE id =
                :filial_id

              AND empresa_id =
                :empresa_id

            LIMIT 1
        ");

        $stmt->execute([
            ':filial_id' =>
            $filialId,

            ':empresa_id' =>
            $empresaId
        ]);

        if (!$stmt->fetchColumn()) {
            throw new RuntimeException(
                'A filial selecionada não pertence à empresa.'
            );
        }
    }

    // ==================================================
    // VALIDAR STATUS
    // ==================================================

    private function validarStatus(
        int $statusId
    ): void {

        $stmt = $this->pdo->prepare("
            SELECT id

            FROM status

            WHERE id =
                :status_id

              AND tipo =
                'calendario_laboral'

            LIMIT 1
        ");

        $stmt->execute([
            ':status_id' =>
            $statusId
        ]);

        if (!$stmt->fetchColumn()) {
            throw new RuntimeException(
                'Estado do evento do calendário laboral inválido.'
            );
        }
    }

    private function statusAtivoId(): int
    {
        $stmt = $this->pdo->query("
            SELECT id

            FROM status

            WHERE tipo =
                'calendario_laboral'

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
                'O estado ativo do calendário laboral não está configurado.'
            );
        }

        return $id;
    }

    // ==================================================
    // VALIDAR DUPLICADO
    // ==================================================

    private function validarCodigoDuplicado(
        int $empresaId,
        string $codigo,
        ?int $ignorarId = null
    ): void {

        $sql = "
            SELECT id

            FROM calendario_laboral

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
                AND id <>
                    :ignorar_id
            ";

            $params[':ignorar_id'] =
                $ignorarId;
        }

        $sql .= "
            LIMIT 1
        ";

        $stmt =
            $this->pdo->prepare(
                $sql
            );

        $stmt->execute(
            $params
        );

        if ($stmt->fetchColumn()) {
            throw new RuntimeException(
                'Já existe um evento com este código.'
            );
        }
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
                'Empresa inválida para o calendário laboral.'
            );
        }
    }

    private function validarId(
        int $eventoId
    ): void {

        if ($eventoId <= 0) {
            throw new RuntimeException(
                'Evento do calendário laboral inválido.'
            );
        }
    }

    // ==================================================
    // TIPOS DE EVENTO
    // ==================================================

    private function tipoEventoObrigatorio(
        mixed $valor
    ): string {

        $tipo =
            strtoupper(
                trim(
                    (string)(
                        $valor ?? ''
                    )
                )
            );

        if (
            !in_array(
                $tipo,
                $this->tiposEventosPermitidos(),
                true
            )
        ) {
            throw new RuntimeException(
                'Tipo de evento do calendário laboral inválido.'
            );
        }

        return $tipo;
    }

    private function tipoEventoOpcional(
        mixed $valor
    ): ?string {

        if (
            $valor === null
            || trim((string)$valor) === ''
        ) {
            return null;
        }

        return $this->tipoEventoObrigatorio(
            $valor
        );
    }

    private function tiposEventosPermitidos(): array
    {
        return [
            'FERIADO_NACIONAL',
            'FERIADO_LOCAL',
            'TOLERANCIA_PONTO',
            'DIA_NAO_UTIL',
            'DIA_UTIL_ESPECIAL',
            'EVENTO_INTERNO',
            'SUSPENSAO_ATIVIDADE',
            'OUTRO'
        ];
    }

    // ==================================================
    // HELPERS DE DATA E HORA
    // ==================================================

    private function dataObrigatoria(
        mixed $valor,
        string $campo
    ): string {

        $data =
            trim(
                (string)(
                    $valor ?? ''
                )
            );

        if ($data === '') {
            throw new RuntimeException(
                'Informe a ' . $campo . '.'
            );
        }

        $objeto =
            DateTimeImmutable::createFromFormat(
                '!Y-m-d',
                $data
            );

        $erros =
            DateTimeImmutable::getLastErrors();

        if (
            $objeto === false
            || (
                is_array($erros)
                && (
                    $erros['warning_count'] > 0
                    || $erros['error_count'] > 0
                )
            )
            || $objeto->format('Y-m-d') !== $data
        ) {
            throw new RuntimeException(
                'A ' . $campo . ' é inválida.'
            );
        }

        return $data;
    }

    private function dataOpcional(
        mixed $valor
    ): ?string {

        if (
            $valor === null
            || trim((string)$valor) === ''
        ) {
            return null;
        }

        return $this->dataObrigatoria(
            $valor,
            'data'
        );
    }

    private function horaOpcional(
        mixed $valor,
        string $campo
    ): ?string {

        if (
            $valor === null
            || trim((string)$valor) === ''
        ) {
            return null;
        }

        $hora =
            trim(
                (string)$valor
            );

        if (
            preg_match(
                '/^\d{2}:\d{2}$/',
                $hora
            )
        ) {
            $hora .= ':00';
        }

        $objeto =
            DateTimeImmutable::createFromFormat(
                '!H:i:s',
                $hora
            );

        $erros =
            DateTimeImmutable::getLastErrors();

        if (
            $objeto === false
            || (
                is_array($erros)
                && (
                    $erros['warning_count'] > 0
                    || $erros['error_count'] > 0
                )
            )
        ) {
            throw new RuntimeException(
                'A ' . $campo . ' é inválida.'
            );
        }

        return $objeto->format(
            'H:i:s'
        );
    }

    // ==================================================
    // HELPERS GERAIS
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

    private function corOpcional(
        mixed $valor
    ): ?string {

        $cor =
            trim(
                (string)(
                    $valor ?? ''
                )
            );

        if ($cor === '') {
            return null;
        }

        if (
            !preg_match(
                '/^#[0-9A-Fa-f]{6}$/',
                $cor
            )
        ) {
            throw new RuntimeException(
                'A cor do evento deve estar no formato hexadecimal, por exemplo #dc3545.'
            );
        }

        return strtolower(
            $cor
        );
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
