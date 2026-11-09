<?php

declare(strict_types=1);

require_once __DIR__
    . '/../services/AuditoriaService.php';

require_once __DIR__
    . '/../services/LogService.php';


final class HorarioTrabalho
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
                horario.id,
                horario.uuid,
                horario.empresa_id,
                horario.codigo,
                horario.nome,
                horario.descricao,
                horario.hora_entrada,
                horario.hora_saida,
                horario.intervalo_inicio,
                horario.intervalo_fim,
                horario.tolerancia_entrada_minutos,
                horario.tolerancia_saida_minutos,
                horario.carga_horaria_diaria,
                horario.carga_horaria_semanal,
                horario.segunda_feira,
                horario.terca_feira,
                horario.quarta_feira,
                horario.quinta_feira,
                horario.sexta_feira,
                horario.sabado,
                horario.domingo,
                horario.permite_horas_extras,
                horario.permite_trabalho_noturno,
                horario.horario_flexivel,
                horario.status_id,
                horario.criado_por,
                horario.atualizado_por,
                horario.criado_em,
                horario.atualizado_em,

                status.nome AS status_nome,
                status.descricao AS status_descricao

            FROM horarios_trabalho horario

            INNER JOIN status
                ON status.id =
                    horario.status_id

               AND status.tipo =
                    'horario_trabalho'

            WHERE horario.empresa_id =
                :empresa_id

            ORDER BY
                horario.nome ASC
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
        int $horarioId
    ): array {

        $this->validarEmpresa(
            $empresaId
        );

        $this->validarId(
            $horarioId
        );

        $stmt = $this->pdo->prepare("
            SELECT
                horario.*,

                status.nome AS status_nome,
                status.descricao AS status_descricao

            FROM horarios_trabalho horario

            INNER JOIN status
                ON status.id =
                    horario.status_id

               AND status.tipo =
                    'horario_trabalho'

            WHERE horario.id =
                :horario_id

              AND horario.empresa_id =
                :empresa_id

            LIMIT 1
        ");

        $stmt->execute([
            ':horario_id' =>
            $horarioId,

            ':empresa_id' =>
            $empresaId
        ]);

        $horario =
            $stmt->fetch(
                PDO::FETCH_ASSOC
            );

        if (!$horario) {
            throw new RuntimeException(
                'Horário de trabalho não encontrado.'
            );
        }

        return $horario;
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
                INSERT INTO horarios_trabalho
                (
                    uuid,
                    empresa_id,
                    codigo,
                    nome,
                    descricao,
                    hora_entrada,
                    hora_saida,
                    intervalo_inicio,
                    intervalo_fim,
                    tolerancia_entrada_minutos,
                    tolerancia_saida_minutos,
                    carga_horaria_diaria,
                    carga_horaria_semanal,
                    segunda_feira,
                    terca_feira,
                    quarta_feira,
                    quinta_feira,
                    sexta_feira,
                    sabado,
                    domingo,
                    permite_horas_extras,
                    permite_trabalho_noturno,
                    horario_flexivel,
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
                    :hora_entrada,
                    :hora_saida,
                    :intervalo_inicio,
                    :intervalo_fim,
                    :tolerancia_entrada_minutos,
                    :tolerancia_saida_minutos,
                    :carga_horaria_diaria,
                    :carga_horaria_semanal,
                    :segunda_feira,
                    :terca_feira,
                    :quarta_feira,
                    :quinta_feira,
                    :sexta_feira,
                    :sabado,
                    :domingo,
                    :permite_horas_extras,
                    :permite_trabalho_noturno,
                    :horario_flexivel,
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

                ':hora_entrada' =>
                $dados['hora_entrada'],

                ':hora_saida' =>
                $dados['hora_saida'],

                ':intervalo_inicio' =>
                $dados['intervalo_inicio'],

                ':intervalo_fim' =>
                $dados['intervalo_fim'],

                ':tolerancia_entrada_minutos' =>
                $dados['tolerancia_entrada_minutos'],

                ':tolerancia_saida_minutos' =>
                $dados['tolerancia_saida_minutos'],

                ':carga_horaria_diaria' =>
                $dados['carga_horaria_diaria'],

                ':carga_horaria_semanal' =>
                $dados['carga_horaria_semanal'],

                ':segunda_feira' =>
                $dados['segunda_feira'],

                ':terca_feira' =>
                $dados['terca_feira'],

                ':quarta_feira' =>
                $dados['quarta_feira'],

                ':quinta_feira' =>
                $dados['quinta_feira'],

                ':sexta_feira' =>
                $dados['sexta_feira'],

                ':sabado' =>
                $dados['sabado'],

                ':domingo' =>
                $dados['domingo'],

                ':permite_horas_extras' =>
                $dados['permite_horas_extras'],

                ':permite_trabalho_noturno' =>
                $dados['permite_trabalho_noturno'],

                ':horario_flexivel' =>
                $dados['horario_flexivel'],

                ':status_id' =>
                $dados['status_id'],

                ':criado_por' =>
                $usuarioId,

                ':atualizado_por' =>
                $usuarioId
            ]);


            $horarioId =
                (int)$this->pdo
                    ->lastInsertId();


            if ($horarioId <= 0) {
                throw new RuntimeException(
                    'Não foi possível identificar o horário de trabalho criado.'
                );
            }


            $horarioDepois =
                $this->buscar(
                    $empresaId,
                    $horarioId
                );


            $this->auditoria->insercao(
                $empresaId,
                $usuarioId,
                'horarios_trabalho',
                $horarioId,
                $horarioDepois
            );


            $this->log->registrar(
                $empresaId,
                $usuarioId,
                'HORARIOS_TRABALHO',
                'CRIAR',
                sprintf(
                    'Criou o horário de trabalho %s - %s.',
                    $horarioDepois['codigo'],
                    $horarioDepois['nome']
                ),
                $horarioId
            );


            $this->pdo->commit();


            return $horarioId;
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
        int $horarioId,
        array $dados
    ): void {

        $this->validarEmpresa(
            $empresaId
        );

        $this->validarUsuario(
            $usuarioId
        );

        $this->validarId(
            $horarioId
        );


        $atual =
            $this->buscar(
                $empresaId,
                $horarioId
            );


        $dados =
            $this->normalizarDados(
                $dados
            );


        $this->validarCodigoDuplicado(
            $empresaId,
            $dados['codigo'],
            $horarioId
        );


        $this->validarNomeDuplicado(
            $empresaId,
            $dados['nome'],
            $horarioId
        );


        $this->validarStatus(
            $dados['status_id']
        );


        $this->pdo->beginTransaction();


        try {

            $stmt =
                $this->pdo->prepare("
                UPDATE horarios_trabalho

                SET
                    codigo =
                        :codigo,

                    nome =
                        :nome,

                    descricao =
                        :descricao,

                    hora_entrada =
                        :hora_entrada,

                    hora_saida =
                        :hora_saida,

                    intervalo_inicio =
                        :intervalo_inicio,

                    intervalo_fim =
                        :intervalo_fim,

                    tolerancia_entrada_minutos =
                        :tolerancia_entrada_minutos,

                    tolerancia_saida_minutos =
                        :tolerancia_saida_minutos,

                    carga_horaria_diaria =
                        :carga_horaria_diaria,

                    carga_horaria_semanal =
                        :carga_horaria_semanal,

                    segunda_feira =
                        :segunda_feira,

                    terca_feira =
                        :terca_feira,

                    quarta_feira =
                        :quarta_feira,

                    quinta_feira =
                        :quinta_feira,

                    sexta_feira =
                        :sexta_feira,

                    sabado =
                        :sabado,

                    domingo =
                        :domingo,

                    permite_horas_extras =
                        :permite_horas_extras,

                    permite_trabalho_noturno =
                        :permite_trabalho_noturno,

                    horario_flexivel =
                        :horario_flexivel,

                    status_id =
                        :status_id,

                    atualizado_por =
                        :atualizado_por

                WHERE id =
                    :horario_id

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

                ':hora_entrada' =>
                $dados['hora_entrada'],

                ':hora_saida' =>
                $dados['hora_saida'],

                ':intervalo_inicio' =>
                $dados['intervalo_inicio'],

                ':intervalo_fim' =>
                $dados['intervalo_fim'],

                ':tolerancia_entrada_minutos' =>
                $dados['tolerancia_entrada_minutos'],

                ':tolerancia_saida_minutos' =>
                $dados['tolerancia_saida_minutos'],

                ':carga_horaria_diaria' =>
                $dados['carga_horaria_diaria'],

                ':carga_horaria_semanal' =>
                $dados['carga_horaria_semanal'],

                ':segunda_feira' =>
                $dados['segunda_feira'],

                ':terca_feira' =>
                $dados['terca_feira'],

                ':quarta_feira' =>
                $dados['quarta_feira'],

                ':quinta_feira' =>
                $dados['quinta_feira'],

                ':sexta_feira' =>
                $dados['sexta_feira'],

                ':sabado' =>
                $dados['sabado'],

                ':domingo' =>
                $dados['domingo'],

                ':permite_horas_extras' =>
                $dados['permite_horas_extras'],

                ':permite_trabalho_noturno' =>
                $dados['permite_trabalho_noturno'],

                ':horario_flexivel' =>
                $dados['horario_flexivel'],

                ':status_id' =>
                $dados['status_id'],

                ':atualizado_por' =>
                $usuarioId,

                ':horario_id' =>
                $horarioId,

                ':empresa_id' =>
                $empresaId
            ]);


            $novo =
                $this->buscar(
                    $empresaId,
                    $horarioId
                );


            if (
                $stmt->rowCount() === 0
                && $this->dadosIguais(
                    $atual,
                    $novo
                )
            ) {
                $this->pdo->commit();

                return;
            }


            $this->auditoria->atualizacao(
                $empresaId,
                $usuarioId,
                'horarios_trabalho',
                $horarioId,
                $atual,
                $novo
            );


            $this->log->registrar(
                $empresaId,
                $usuarioId,
                'HORARIOS_TRABALHO',
                'EDITAR',
                sprintf(
                    'Atualizou o horário de trabalho %s - %s.',
                    $novo['codigo'],
                    $novo['nome']
                ),
                $horarioId
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

    private function dadosIguais(
        array $anterior,
        array $novo
    ): bool {

        $campos = [
            'codigo',
            'nome',
            'descricao',
            'hora_entrada',
            'hora_saida',
            'intervalo_inicio',
            'intervalo_fim',
            'tolerancia_entrada_minutos',
            'tolerancia_saida_minutos',
            'carga_horaria_diaria',
            'carga_horaria_semanal',
            'segunda_feira',
            'terca_feira',
            'quarta_feira',
            'quinta_feira',
            'sexta_feira',
            'sabado',
            'domingo',
            'permite_horas_extras',
            'permite_trabalho_noturno',
            'horario_flexivel',
            'status_id'
        ];


        foreach ($campos as $campo) {

            $valorAnterior =
                $anterior[$campo]
                ?? null;

            $valorNovo =
                $novo[$campo]
                ?? null;


            if (
                (string)$valorAnterior
                !== (string)$valorNovo
            ) {
                return false;
            }
        }


        return true;
    }

    // ==================================================
    // ELIMINAR
    // ==================================================

    public function eliminar(
        int $empresaId,
        int $usuarioId,
        int $horarioId
    ): void {

        $this->validarEmpresa(
            $empresaId
        );

        $this->validarUsuario(
            $usuarioId
        );

        $this->validarId(
            $horarioId
        );


        $horario =
            $this->buscar(
                $empresaId,
                $horarioId
            );


        /*
     * Mantém-se aqui a validação de utilização
     * por funcionários/contratos quando aplicável.
     */


        $this->pdo->beginTransaction();


        try {

            $stmt =
                $this->pdo->prepare("
                DELETE FROM horarios_trabalho

                WHERE id =
                    :horario_id

                  AND empresa_id =
                    :empresa_id
            ");


            $stmt->execute([
                ':horario_id' =>
                $horarioId,

                ':empresa_id' =>
                $empresaId
            ]);


            if (
                $stmt->rowCount()
                !== 1
            ) {
                throw new RuntimeException(
                    'Não foi possível eliminar o horário de trabalho.'
                );
            }


            $this->auditoria->exclusao(
                $empresaId,
                $usuarioId,
                'horarios_trabalho',
                $horarioId,
                $horario
            );


            $this->log->registrar(
                $empresaId,
                $usuarioId,
                'HORARIOS_TRABALHO',
                'ELIMINAR',
                sprintf(
                    'Eliminou o horário de trabalho %s - %s.',
                    $horario['codigo'],
                    $horario['nome']
                ),
                $horarioId
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
            'horarios' =>
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

        $horaEntrada =
            $this->normalizarHora(
                $dados['hora_entrada']
                    ?? null,
                'hora de entrada'
            );

        $horaSaida =
            $this->normalizarHora(
                $dados['hora_saida']
                    ?? null,
                'hora de saída'
            );

        $intervaloInicio =
            $this->normalizarHoraOpcional(
                $dados['intervalo_inicio']
                    ?? null,
                'início do intervalo'
            );

        $intervaloFim =
            $this->normalizarHoraOpcional(
                $dados['intervalo_fim']
                    ?? null,
                'fim do intervalo'
            );

        $toleranciaEntrada =
            $this->inteiroNaoNegativo(
                $dados['tolerancia_entrada_minutos']
                    ?? 0,
                'tolerância de entrada'
            );

        $toleranciaSaida =
            $this->inteiroNaoNegativo(
                $dados['tolerancia_saida_minutos']
                    ?? 0,
                'tolerância de saída'
            );

        $segundaFeira =
            $this->booleano(
                $dados['segunda_feira']
                    ?? 0
            );

        $tercaFeira =
            $this->booleano(
                $dados['terca_feira']
                    ?? 0
            );

        $quartaFeira =
            $this->booleano(
                $dados['quarta_feira']
                    ?? 0
            );

        $quintaFeira =
            $this->booleano(
                $dados['quinta_feira']
                    ?? 0
            );

        $sextaFeira =
            $this->booleano(
                $dados['sexta_feira']
                    ?? 0
            );

        $sabado =
            $this->booleano(
                $dados['sabado']
                    ?? 0
            );

        $domingo =
            $this->booleano(
                $dados['domingo']
                    ?? 0
            );

        $permiteHorasExtras =
            $this->booleano(
                $dados['permite_horas_extras']
                    ?? 0
            );

        $permiteTrabalhoNoturno =
            $this->booleano(
                $dados['permite_trabalho_noturno']
                    ?? 0
            );

        $horarioFlexivel =
            $this->booleano(
                $dados['horario_flexivel']
                    ?? 0
            );

        $statusId =
            (int)(
                $dados['status_id']
                ?? 0
            );

        if ($codigo === '') {
            throw new RuntimeException(
                'Informe o código do horário de trabalho.'
            );
        }

        if (mb_strlen($codigo) > 20) {
            throw new RuntimeException(
                'O código do horário não pode exceder 20 caracteres.'
            );
        }

        if ($nome === '') {
            throw new RuntimeException(
                'Informe o nome do horário de trabalho.'
            );
        }

        if (mb_strlen($nome) > 120) {
            throw new RuntimeException(
                'O nome do horário não pode exceder 120 caracteres.'
            );
        }

        if ($horaEntrada === $horaSaida) {
            throw new RuntimeException(
                'A hora de entrada não pode ser igual à hora de saída.'
            );
        }

        if (
            ($intervaloInicio === null)
            !== ($intervaloFim === null)
        ) {
            throw new RuntimeException(
                'Informe o início e o fim do intervalo.'
            );
        }

        if (
            $intervaloInicio !== null
            && $intervaloFim !== null
            && $intervaloInicio === $intervaloFim
        ) {
            throw new RuntimeException(
                'O início e o fim do intervalo não podem ser iguais.'
            );
        }

        if (
            $toleranciaEntrada > 1440
            || $toleranciaSaida > 1440
        ) {
            throw new RuntimeException(
                'A tolerância não pode exceder 1440 minutos.'
            );
        }

        $diasTrabalho =
            $segundaFeira
            + $tercaFeira
            + $quartaFeira
            + $quintaFeira
            + $sextaFeira
            + $sabado
            + $domingo;

        if ($diasTrabalho <= 0) {
            throw new RuntimeException(
                'Selecione pelo menos um dia de trabalho.'
            );
        }

        $cargaDiariaCalculada =
            $this->calcularCargaHorariaDiaria(
                $horaEntrada,
                $horaSaida,
                $intervaloInicio,
                $intervaloFim
            );

        if ($cargaDiariaCalculada <= 0) {
            throw new RuntimeException(
                'A carga horária diária calculada é inválida.'
            );
        }

        if ($cargaDiariaCalculada > 24) {
            throw new RuntimeException(
                'A carga horária diária não pode exceder 24 horas.'
            );
        }

        $cargaSemanalCalculada =
            round(
                $cargaDiariaCalculada
                    * $diasTrabalho,
                2
            );

        if ($cargaSemanalCalculada > 168) {
            throw new RuntimeException(
                'A carga horária semanal não pode exceder 168 horas.'
            );
        }

        $atravessaMeiaNoite =
            $this->horaParaSegundos(
                $horaSaida
            )
            <=
            $this->horaParaSegundos(
                $horaEntrada
            );

        if (
            $atravessaMeiaNoite
            && $permiteTrabalhoNoturno !== 1
        ) {
            throw new RuntimeException(
                'Horários que atravessam a meia-noite devem permitir trabalho noturno.'
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

            'hora_entrada' =>
            $horaEntrada,

            'hora_saida' =>
            $horaSaida,

            'intervalo_inicio' =>
            $intervaloInicio,

            'intervalo_fim' =>
            $intervaloFim,

            'tolerancia_entrada_minutos' =>
            $toleranciaEntrada,

            'tolerancia_saida_minutos' =>
            $toleranciaSaida,

            'carga_horaria_diaria' =>
            $cargaDiariaCalculada,

            'carga_horaria_semanal' =>
            $cargaSemanalCalculada,

            'segunda_feira' =>
            $segundaFeira,

            'terca_feira' =>
            $tercaFeira,

            'quarta_feira' =>
            $quartaFeira,

            'quinta_feira' =>
            $quintaFeira,

            'sexta_feira' =>
            $sextaFeira,

            'sabado' =>
            $sabado,

            'domingo' =>
            $domingo,

            'permite_horas_extras' =>
            $permiteHorasExtras,

            'permite_trabalho_noturno' =>
            $permiteTrabalhoNoturno,

            'horario_flexivel' =>
            $horarioFlexivel,

            'status_id' =>
            $statusId
        ];
    }

    // ==================================================
    // CÁLCULO DA CARGA HORÁRIA
    // ==================================================

    private function calcularCargaHorariaDiaria(
        string $horaEntrada,
        string $horaSaida,
        ?string $intervaloInicio,
        ?string $intervaloFim
    ): float {

        $entrada =
            $this->horaParaSegundos(
                $horaEntrada
            );

        $saida =
            $this->horaParaSegundos(
                $horaSaida
            );

        if ($saida <= $entrada) {
            $saida += 86400;
        }

        $duracaoTrabalho =
            $saida - $entrada;

        $duracaoIntervalo =
            0;

        if (
            $intervaloInicio !== null
            && $intervaloFim !== null
        ) {
            $inicioIntervalo =
                $this->horaParaSegundos(
                    $intervaloInicio
                );

            $fimIntervalo =
                $this->horaParaSegundos(
                    $intervaloFim
                );

            if ($inicioIntervalo < $entrada) {
                $inicioIntervalo += 86400;
            }

            if ($fimIntervalo <= $inicioIntervalo) {
                $fimIntervalo += 86400;
            }

            if (
                $inicioIntervalo < $entrada
                || $fimIntervalo > $saida
            ) {
                throw new RuntimeException(
                    'O intervalo deve estar dentro do período de trabalho.'
                );
            }

            $duracaoIntervalo =
                $fimIntervalo
                - $inicioIntervalo;
        }

        $segundosLiquidos =
            $duracaoTrabalho
            - $duracaoIntervalo;

        if ($segundosLiquidos <= 0) {
            throw new RuntimeException(
                'O intervalo não pode ocupar toda a jornada de trabalho.'
            );
        }

        return round(
            $segundosLiquidos / 3600,
            2
        );
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

            FROM horarios_trabalho

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
                'Já existe um horário com este código.'
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

            FROM horarios_trabalho

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
                'Já existe um horário com este nome.'
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
                'horario_trabalho'

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
                'horario_trabalho'

            LIMIT 1
        ");

        $stmt->execute([
            ':status_id' =>
            $statusId
        ]);

        if (!$stmt->fetchColumn()) {
            throw new RuntimeException(
                'Estado do horário de trabalho inválido.'
            );
        }
    }

    private function statusAtivoId(): int
    {
        $stmt = $this->pdo->query("
            SELECT id

            FROM status

            WHERE tipo =
                'horario_trabalho'

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
                'O estado ativo do horário de trabalho não está configurado.'
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
                'Empresa inválida para a gestão dos horários de trabalho.'
            );
        }
    }

    private function validarId(
        int $horarioId
    ): void {

        if ($horarioId <= 0) {
            throw new RuntimeException(
                'Horário de trabalho inválido.'
            );
        }
    }

    // ==================================================
    // HELPERS
    // ==================================================

    private function normalizarHora(
        mixed $valor,
        string $campo
    ): string {

        $hora =
            trim(
                (string)(
                    $valor ?? ''
                )
            );

        if ($hora === '') {
            throw new RuntimeException(
                'Informe a ' . $campo . '.'
            );
        }

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

    private function normalizarHoraOpcional(
        mixed $valor,
        string $campo
    ): ?string {

        if (
            $valor === null
            || trim((string)$valor) === ''
        ) {
            return null;
        }

        return $this->normalizarHora(
            $valor,
            $campo
        );
    }

    private function horaParaSegundos(
        string $hora
    ): int {

        [
            $horas,
            $minutos,
            $segundos
        ] = array_map(
            'intval',
            explode(
                ':',
                $hora
            )
        );

        return (
            $horas * 3600
            + $minutos * 60
            + $segundos
        );
    }

    private function inteiroNaoNegativo(
        mixed $valor,
        string $campo
    ): int {

        if (
            $valor === null
            || $valor === ''
        ) {
            return 0;
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
                'A ' . $campo . ' é inválida.'
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
