<?php

declare(strict_types=1);

final class Funcionario
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

        $filialId =
            $this->inteiroOpcional(
                $filtros['filial_id']
                    ?? null
            );

        $departamentoId =
            $this->inteiroOpcional(
                $filtros['departamento_id']
                    ?? null
            );

        $cargoId =
            $this->inteiroOpcional(
                $filtros['cargo_id']
                    ?? null
            );

        $statusId =
            $this->inteiroOpcional(
                $filtros['status_id']
                    ?? null
            );

        $pesquisa =
            $this->textoOpcional(
                $filtros['pesquisa']
                    ?? null
            );

        $sql = "
            SELECT
                funcionario.id,
                funcionario.uuid,
                funcionario.empresa_id,
                funcionario.filial_id,
                funcionario.departamento_id,
                funcionario.cargo_id,
                funcionario.horario_trabalho_id,
                funcionario.numero_funcionario,
                funcionario.primeiro_nome,
                funcionario.apelido,
                funcionario.nome_completo,
                funcionario.sexo,
                funcionario.data_nascimento,
                funcionario.estado_civil,
                funcionario.nacionalidade,
                funcionario.naturalidade,
                funcionario.nuit,
                funcionario.numero_bi,
                funcionario.data_emissao_bi,
                funcionario.data_validade_bi,
                funcionario.entidade_emissora_bi,
                funcionario.numero_inss,
                funcionario.email_pessoal,
                funcionario.email_profissional,
                funcionario.telefone_principal,
                funcionario.telefone_alternativo,
                funcionario.endereco,
                funcionario.provincia,
                funcionario.distrito,
                funcionario.cidade,
                funcionario.bairro,
                funcionario.contacto_emergencia_nome,
                funcionario.contacto_emergencia_parentesco,
                funcionario.contacto_emergencia_telefone,
                funcionario.data_admissao,
                funcionario.data_desligamento,
                funcionario.motivo_desligamento,
                funcionario.tipo_vinculo,
                funcionario.salario_base,
                funcionario.moeda,
                funcionario.numero_conta_bancaria,
                funcionario.nib,
                funcionario.banco_nome,
                funcionario.fotografia,
                funcionario.observacoes,
                funcionario.status_id,
                funcionario.criado_por,
                funcionario.atualizado_por,
                funcionario.criado_em,
                funcionario.atualizado_em,

                filial.nome AS filial_nome,
                filial.codigo AS filial_codigo,

                departamento.nome AS departamento_nome,
                departamento.codigo AS departamento_codigo,

                cargo.nome AS cargo_nome,
                cargo.codigo AS cargo_codigo,

                horario.nome AS horario_nome,
                horario.codigo AS horario_codigo,

                status.nome AS status_nome,
                status.descricao AS status_descricao

            FROM funcionarios funcionario

            LEFT JOIN filiais filial
                ON filial.id =
                    funcionario.filial_id

               AND filial.empresa_id =
                    funcionario.empresa_id

            LEFT JOIN departamentos departamento
                ON departamento.id =
                    funcionario.departamento_id

               AND departamento.empresa_id =
                    funcionario.empresa_id

            LEFT JOIN cargos cargo
                ON cargo.id =
                    funcionario.cargo_id

               AND cargo.empresa_id =
                    funcionario.empresa_id

            LEFT JOIN horarios_trabalho horario
                ON horario.id =
                    funcionario.horario_trabalho_id

               AND horario.empresa_id =
                    funcionario.empresa_id

            INNER JOIN status
                ON status.id =
                    funcionario.status_id

               AND status.tipo =
                    'funcionario'

            WHERE funcionario.empresa_id =
                :empresa_id
        ";

        $params = [
            ':empresa_id' =>
            $empresaId
        ];

        if ($filialId !== null) {

            $sql .= "
                AND funcionario.filial_id =
                    :filial_id
            ";

            $params[':filial_id'] =
                $filialId;
        }

        if ($departamentoId !== null) {

            $sql .= "
                AND funcionario.departamento_id =
                    :departamento_id
            ";

            $params[':departamento_id'] =
                $departamentoId;
        }

        if ($cargoId !== null) {

            $sql .= "
                AND funcionario.cargo_id =
                    :cargo_id
            ";

            $params[':cargo_id'] =
                $cargoId;
        }

        if ($statusId !== null) {

            $sql .= "
                AND funcionario.status_id =
                    :status_id
            ";

            $params[':status_id'] =
                $statusId;
        }

        if ($pesquisa !== null) {

            $sql .= "
                AND
                (
                    funcionario.nome_completo
                        LIKE :pesquisa

                    OR funcionario.numero_funcionario
                        LIKE :pesquisa

                    OR funcionario.nuit
                        LIKE :pesquisa

                    OR funcionario.numero_bi
                        LIKE :pesquisa

                    OR funcionario.email_profissional
                        LIKE :pesquisa

                    OR funcionario.telefone_principal
                        LIKE :pesquisa
                )
            ";

            $params[':pesquisa'] =
                '%' . $pesquisa . '%';
        }

        $sql .= "
            ORDER BY
                funcionario.nome_completo ASC
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

    public function processarArquivosFuncionario(
        int $empresaId,
        int $usuarioId,
        int $funcionarioId,
        array $arquivos
    ): void {

        if (!$arquivos) {
            return;
        }


        /*
    |--------------------------------------------------------------------------
    | FOTOGRAFIA
    |--------------------------------------------------------------------------
    */

        if (
            isset(
                $arquivos['fotografia_arquivo']
            )
        ) {

            $fotografia =
                $arquivos['fotografia_arquivo'];

            $erroFoto =
                (int)(
                    $fotografia['error']
                    ?? UPLOAD_ERR_NO_FILE
                );


            if (
                $erroFoto !==
                UPLOAD_ERR_NO_FILE
            ) {

                $this->validarErroUpload(
                    $fotografia,
                    'A fotografia'
                );


                $caminho =
                    $this->guardarArquivoFuncionario(
                        $empresaId,
                        $funcionarioId,
                        $fotografia,
                        'fotografia',
                        [
                            'image/jpeg',
                            'image/png',
                            'image/webp'
                        ],
                        5 * 1024 * 1024
                    );


                $stmt =
                    $this->pdo->prepare("
                UPDATE funcionarios

                SET
                    fotografia =
                        :fotografia,

                    atualizado_por =
                        :usuario_id

                WHERE id =
                    :funcionario_id

                  AND empresa_id =
                    :empresa_id
            ");


                $stmt->execute([
                    ':fotografia' =>
                    $caminho,

                    ':usuario_id' =>
                    $usuarioId,

                    ':funcionario_id' =>
                    $funcionarioId,

                    ':empresa_id' =>
                    $empresaId
                ]);
            }
        }


        /*
|--------------------------------------------------------------------------
| DOCUMENTOS
|--------------------------------------------------------------------------
*/

        $documentos = [

            'documento_bi' =>
            'BI',

            'documento_passaporte' =>
            'PASSAPORTE',

            'documento_nuit' =>
            'NUIT',

            'documento_inss' =>
            'INSS',

            'documento_registo_criminal' =>
            'REGISTO_CRIMINAL',

            'documento_habilitacoes' =>
            'HABILITACOES',

            'documento_cv' =>
            'CV',

            'documento_atestado_sanidade' =>
            'ATESTADO_SANIDADE'
        ];


        foreach (
            $documentos
            as $campo => $tipo
        ) {

            /*
    |--------------------------------------------------------------------------
    | CAMPO NÃO VEIO NA REQUISIÇÃO
    |--------------------------------------------------------------------------
    */

            if (
                !isset(
                    $arquivos[$campo]
                )
            ) {
                continue;
            }


            $arquivo =
                $arquivos[$campo];


            /*
    |--------------------------------------------------------------------------
    | NENHUM FICHEIRO ESCOLHIDO
    |--------------------------------------------------------------------------
    */

            $erro =
                (int)(
                    $arquivo['error']
                    ?? UPLOAD_ERR_NO_FILE
                );


            if (
                $erro ===
                UPLOAD_ERR_NO_FILE
            ) {
                continue;
            }


            /*
    |--------------------------------------------------------------------------
    | VALIDAR ERROS DO PHP
    |--------------------------------------------------------------------------
    */

            $this->validarErroUpload(
                $arquivo,
                'O documento '
                    . $tipo
            );


            /*
    |--------------------------------------------------------------------------
    | MIME TYPES
    |--------------------------------------------------------------------------
    */

            if ($tipo === 'CV') {

                $permitidos = [
                    'application/pdf',
                    'application/msword',
                    'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
                ];
            } else {

                $permitidos = [
                    'application/pdf',
                    'image/jpeg',
                    'image/png'
                ];
            }


            /*
    |--------------------------------------------------------------------------
    | GUARDAR FICHEIRO FISICAMENTE
    |--------------------------------------------------------------------------
    */

            $caminho =
                $this->guardarArquivoFuncionario(
                    $empresaId,
                    $funcionarioId,
                    $arquivo,
                    'documentos',
                    $permitidos,
                    10 * 1024 * 1024
                );


            /*
    |--------------------------------------------------------------------------
    | MIME REAL
    |--------------------------------------------------------------------------
    */

            $finfo =
                new finfo(
                    FILEINFO_MIME_TYPE
                );

            $mimeReal =
                $finfo->file(
                    dirname(
                        __DIR__,
                        2
                    )
                        . str_replace(
                            '/erpplus',
                            '',
                            $caminho
                        )
                );


            /*
    |--------------------------------------------------------------------------
    | INSERT DO DOCUMENTO
    |--------------------------------------------------------------------------
    */

            $stmt =
                $this->pdo->prepare("
            INSERT INTO funcionario_documentos
            (
                empresa_id,
                funcionario_id,
                tipo,
                nome_original,
                caminho_arquivo,
                mime_type,
                tamanho_bytes,
                criado_por
            )
            VALUES
            (
                :empresa_id,
                :funcionario_id,
                :tipo,
                :nome_original,
                :caminho_arquivo,
                :mime_type,
                :tamanho_bytes,
                :criado_por
            )
        ");


            $stmt->execute([

                ':empresa_id' =>
                $empresaId,

                ':funcionario_id' =>
                $funcionarioId,

                ':tipo' =>
                $tipo,

                ':nome_original' =>
                (string)(
                    $arquivo['name']
                    ?? ''
                ),

                ':caminho_arquivo' =>
                $caminho,

                ':mime_type' =>
                $mimeReal
                    ?: (
                        $arquivo['type']
                        ?? null
                    ),

                ':tamanho_bytes' =>
                (int)(
                    $arquivo['size']
                    ?? 0
                ),

                ':criado_por' =>
                $usuarioId
            ]);
        }
    }

    private function arquivoRecebido(
        array $arquivo
    ): bool {

        return (
            isset(
                $arquivo['error']
            )
            && (int)$arquivo['error']
            === UPLOAD_ERR_OK
            && !empty($arquivo['tmp_name'])
        );
    }

    private function guardarArquivoFuncionario(
        int $empresaId,
        int $funcionarioId,
        array $arquivo,
        string $subpasta,
        array $mimePermitidos,
        int $tamanhoMaximo
    ): string {

        if (
            !$this->arquivoRecebido(
                $arquivo
            )
        ) {
            throw new RuntimeException(
                'O ficheiro enviado é inválido.'
            );
        }


        if (
            (int)$arquivo['size']
            > $tamanhoMaximo
        ) {
            throw new RuntimeException(
                'O ficheiro excede o tamanho máximo permitido.'
            );
        }


        $finfo =
            new finfo(
                FILEINFO_MIME_TYPE
            );

        $mime =
            $finfo->file(
                $arquivo['tmp_name']
            );


        if (
            $mime === false
            || !in_array(
                $mime,
                $mimePermitidos,
                true
            )
        ) {
            throw new RuntimeException(
                'Tipo de ficheiro não permitido.'
            );
        }


        $extensoes = [
            'image/jpeg' =>
            'jpg',

            'image/png' =>
            'png',

            'image/webp' =>
            'webp',

            'application/pdf' =>
            'pdf',

            'application/msword' =>
            'doc',

            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' =>
            'docx'
        ];


        $extensao =
            $extensoes[$mime]
            ?? null;


        if ($extensao === null) {
            throw new RuntimeException(
                'Extensão de ficheiro não suportada.'
            );
        }


        $pastaFisica =
            dirname(
                __DIR__,
                2
            )
            . '/uploads/funcionarios/'
            . 'empresa_'
            . $empresaId
            . '/funcionario_'
            . $funcionarioId
            . '/'
            . $subpasta;


        if (
            !is_dir(
                $pastaFisica
            )
            && !mkdir(
                $pastaFisica,
                0775,
                true
            )
            && !is_dir(
                $pastaFisica
            )
        ) {
            throw new RuntimeException(
                'Não foi possível criar a pasta do funcionário.'
            );
        }


        $nomeArquivo =
            bin2hex(
                random_bytes(16)
            )
            . '.'
            . $extensao;


        $destino =
            $pastaFisica
            . '/'
            . $nomeArquivo;


        if (
            !move_uploaded_file(
                $arquivo['tmp_name'],
                $destino
            )
        ) {
            throw new RuntimeException(
                'Não foi possível guardar o ficheiro.'
            );
        }


        return
            '/erpplus/uploads/funcionarios/'
            . 'empresa_'
            . $empresaId
            . '/funcionario_'
            . $funcionarioId
            . '/'
            . $subpasta
            . '/'
            . $nomeArquivo;
    }

    private function validarErroUpload(
        array $arquivo,
        string $descricao
    ): void {

        $erro =
            isset($arquivo['error'])
            ? (int)$arquivo['error']
            : UPLOAD_ERR_NO_FILE;


        switch ($erro) {

            case UPLOAD_ERR_OK:
                return;


            case UPLOAD_ERR_NO_FILE:
                return;


            case UPLOAD_ERR_INI_SIZE:

                throw new RuntimeException(
                    $descricao
                        . ' excede o limite upload_max_filesize configurado no PHP.'
                );


            case UPLOAD_ERR_FORM_SIZE:

                throw new RuntimeException(
                    $descricao
                        . ' excede o tamanho máximo permitido pelo formulário.'
                );


            case UPLOAD_ERR_PARTIAL:

                throw new RuntimeException(
                    $descricao
                        . ' foi enviado apenas parcialmente.'
                );


            case UPLOAD_ERR_NO_TMP_DIR:

                throw new RuntimeException(
                    'A pasta temporária de uploads não está configurada.'
                );


            case UPLOAD_ERR_CANT_WRITE:

                throw new RuntimeException(
                    'Não foi possível gravar '
                        . $descricao
                        . ' no servidor.'
                );


            case UPLOAD_ERR_EXTENSION:

                throw new RuntimeException(
                    'O upload de '
                        . $descricao
                        . ' foi bloqueado por uma extensão do PHP.'
                );


            default:

                throw new RuntimeException(
                    'Ocorreu um erro desconhecido ao enviar '
                        . $descricao
                        . '. Código: '
                        . $erro
                );
        }
    }

    // ==================================================
    // BUSCAR
    // ==================================================

    public function buscar(
        int $empresaId,
        int $funcionarioId
    ): array {

        $this->validarEmpresa(
            $empresaId
        );

        $this->validarId(
            $funcionarioId
        );

        $stmt = $this->pdo->prepare("
            SELECT
                funcionario.*,

                filial.nome AS filial_nome,
                filial.codigo AS filial_codigo,

                departamento.nome AS departamento_nome,
                departamento.codigo AS departamento_codigo,

                cargo.nome AS cargo_nome,
                cargo.codigo AS cargo_codigo,

                horario.nome AS horario_nome,
                horario.codigo AS horario_codigo,

                status.nome AS status_nome,
                status.descricao AS status_descricao

            FROM funcionarios funcionario

            LEFT JOIN filiais filial
                ON filial.id =
                    funcionario.filial_id

               AND filial.empresa_id =
                    funcionario.empresa_id

            LEFT JOIN departamentos departamento
                ON departamento.id =
                    funcionario.departamento_id

               AND departamento.empresa_id =
                    funcionario.empresa_id

            LEFT JOIN cargos cargo
                ON cargo.id =
                    funcionario.cargo_id

               AND cargo.empresa_id =
                    funcionario.empresa_id

            LEFT JOIN horarios_trabalho horario
                ON horario.id =
                    funcionario.horario_trabalho_id

               AND horario.empresa_id =
                    funcionario.empresa_id

            INNER JOIN status
                ON status.id =
                    funcionario.status_id

               AND status.tipo =
                    'funcionario'

            WHERE funcionario.id =
                :funcionario_id

              AND funcionario.empresa_id =
                :empresa_id

            LIMIT 1
        ");

        $stmt->execute([
            ':funcionario_id' =>
            $funcionarioId,

            ':empresa_id' =>
            $empresaId
        ]);

        $funcionario =
            $stmt->fetch(
                PDO::FETCH_ASSOC
            );

        if (!$funcionario) {
            throw new RuntimeException(
                'Funcionário não encontrado.'
            );
        }

        $dadosFiscais =
            $this->buscarDadosFiscais(
                $funcionarioId
            );


        $funcionario['numero_dependentes'] =
            (int)(
                $dadosFiscais['numero_dependentes']
                ?? 0
            );


        $funcionario['residente_fiscal'] =
            (int)(
                $dadosFiscais['residente_fiscal']
                ?? 1
            );


        /*
        * O NUIT principal continua vindo
        * de funcionarios.nuit.
        *
        * Apenas usamos o fiscal como fallback.
        */
        if (
            empty($funcionario['nuit'])
            && !empty($dadosFiscais['nuit'])
        ) {

            $funcionario['nuit'] =
                $dadosFiscais['nuit'];
        }


        return $funcionario;
    }

    public function listarDocumentos(
        int $empresaId,
        int $funcionarioId
    ): array {

        $this->validarEmpresa(
            $empresaId
        );

        $this->validarId(
            $funcionarioId
        );


        $stmt =
            $this->pdo->prepare("
            SELECT
                id,
                empresa_id,
                funcionario_id,
                tipo,
                numero_documento,
                data_emissao,
                data_validade,
                nome_original,
                caminho_arquivo,
                mime_type,
                tamanho_bytes,
                observacoes,
                criado_por,
                criado_em,
                atualizado_em

            FROM funcionario_documentos

            WHERE empresa_id =
                :empresa_id

              AND funcionario_id =
                :funcionario_id

            ORDER BY
                tipo ASC,
                criado_em DESC,
                id DESC
        ");


        $stmt->execute([
            ':empresa_id' =>
            $empresaId,

            ':funcionario_id' =>
            $funcionarioId
        ]);


        return $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );
    }

    private function guardarDadosFiscais(
        int $funcionarioId,
        array $dados
    ): void {

        if ($funcionarioId <= 0) {
            throw new RuntimeException(
                'Funcionário inválido para os dados fiscais.'
            );
        }


        $numeroDependentes =
            max(
                0,
                (int)(
                    $dados['numero_dependentes']
                    ?? 0
                )
            );


        $residenteFiscal =
            isset(
                $dados['residente_fiscal']
            )
            ? (int)$dados['residente_fiscal']
            : 1;


        $residenteFiscal =
            $residenteFiscal === 1
            ? 1
            : 0;


        $nuit =
            trim(
                (string)(
                    $dados['nuit']
                    ?? ''
                )
            );


        $nuit =
            $nuit !== ''
            ? $nuit
            : null;


        $stmt =
            $this->pdo->prepare("
            INSERT INTO funcionario_dados_fiscais
            (
                funcionario_id,
                numero_dependentes,
                residente_fiscal,
                nuit
            )
            VALUES
            (
                :funcionario_id,
                :numero_dependentes,
                :residente_fiscal,
                :nuit
            )

            ON DUPLICATE KEY UPDATE

                numero_dependentes =
                    VALUES(numero_dependentes),

                residente_fiscal =
                    VALUES(residente_fiscal),

                nuit =
                    VALUES(nuit),

                atualizado_em =
                    CURRENT_TIMESTAMP
        ");


        $stmt->execute([
            ':funcionario_id' =>
            $funcionarioId,

            ':numero_dependentes' =>
            $numeroDependentes,

            ':residente_fiscal' =>
            $residenteFiscal,

            ':nuit' =>
            $nuit
        ]);
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

        $this->validarRelacionamentos(
            $empresaId,
            $dados
        );

        $this->validarDuplicados(
            $empresaId,
            $dados
        );

        $stmt = $this->pdo->prepare("
            INSERT INTO funcionarios
            (
                uuid,
                empresa_id,
                filial_id,
                departamento_id,
                cargo_id,
                horario_trabalho_id,
                numero_funcionario,
                primeiro_nome,
                apelido,
                nome_completo,
                sexo,
                data_nascimento,
                estado_civil,
                nacionalidade,
                naturalidade,
                nuit,
                numero_bi,
                data_emissao_bi,
                data_validade_bi,
                entidade_emissora_bi,
                numero_inss,
                email_pessoal,
                email_profissional,
                telefone_principal,
                telefone_alternativo,
                endereco,
                provincia,
                distrito,
                cidade,
                bairro,
                contacto_emergencia_nome,
                contacto_emergencia_parentesco,
                contacto_emergencia_telefone,
                data_admissao,
                data_desligamento,
                motivo_desligamento,
                tipo_vinculo,
                salario_base,
                moeda,
                numero_conta_bancaria,
                nib,
                banco_nome,
                fotografia,
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
                :departamento_id,
                :cargo_id,
                :horario_trabalho_id,
                :numero_funcionario,
                :primeiro_nome,
                :apelido,
                :nome_completo,
                :sexo,
                :data_nascimento,
                :estado_civil,
                :nacionalidade,
                :naturalidade,
                :nuit,
                :numero_bi,
                :data_emissao_bi,
                :data_validade_bi,
                :entidade_emissora_bi,
                :numero_inss,
                :email_pessoal,
                :email_profissional,
                :telefone_principal,
                :telefone_alternativo,
                :endereco,
                :provincia,
                :distrito,
                :cidade,
                :bairro,
                :contacto_emergencia_nome,
                :contacto_emergencia_parentesco,
                :contacto_emergencia_telefone,
                :data_admissao,
                :data_desligamento,
                :motivo_desligamento,
                :tipo_vinculo,
                :salario_base,
                :moeda,
                :numero_conta_bancaria,
                :nib,
                :banco_nome,
                :fotografia,
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

            ':departamento_id' =>
            $dados['departamento_id'],

            ':cargo_id' =>
            $dados['cargo_id'],

            ':horario_trabalho_id' =>
            $dados['horario_trabalho_id'],

            ':numero_funcionario' =>
            $dados['numero_funcionario'],

            ':primeiro_nome' =>
            $dados['primeiro_nome'],

            ':apelido' =>
            $dados['apelido'],

            ':nome_completo' =>
            $dados['nome_completo'],

            ':sexo' =>
            $dados['sexo'],

            ':data_nascimento' =>
            $dados['data_nascimento'],

            ':estado_civil' =>
            $dados['estado_civil'],

            ':nacionalidade' =>
            $dados['nacionalidade'],

            ':naturalidade' =>
            $dados['naturalidade'],

            ':nuit' =>
            $dados['nuit'],

            ':numero_bi' =>
            $dados['numero_bi'],

            ':data_emissao_bi' =>
            $dados['data_emissao_bi'],

            ':data_validade_bi' =>
            $dados['data_validade_bi'],

            ':entidade_emissora_bi' =>
            $dados['entidade_emissora_bi'],

            ':numero_inss' =>
            $dados['numero_inss'],

            ':email_pessoal' =>
            $dados['email_pessoal'],

            ':email_profissional' =>
            $dados['email_profissional'],

            ':telefone_principal' =>
            $dados['telefone_principal'],

            ':telefone_alternativo' =>
            $dados['telefone_alternativo'],

            ':endereco' =>
            $dados['endereco'],

            ':provincia' =>
            $dados['provincia'],

            ':distrito' =>
            $dados['distrito'],

            ':cidade' =>
            $dados['cidade'],

            ':bairro' =>
            $dados['bairro'],

            ':contacto_emergencia_nome' =>
            $dados['contacto_emergencia_nome'],

            ':contacto_emergencia_parentesco' =>
            $dados['contacto_emergencia_parentesco'],

            ':contacto_emergencia_telefone' =>
            $dados['contacto_emergencia_telefone'],

            ':data_admissao' =>
            $dados['data_admissao'],

            ':data_desligamento' =>
            $dados['data_desligamento'],

            ':motivo_desligamento' =>
            $dados['motivo_desligamento'],

            ':tipo_vinculo' =>
            $dados['tipo_vinculo'],

            ':salario_base' =>
            $dados['salario_base'],

            ':moeda' =>
            $dados['moeda'],

            ':numero_conta_bancaria' =>
            $dados['numero_conta_bancaria'],

            ':nib' =>
            $dados['nib'],

            ':banco_nome' =>
            $dados['banco_nome'],

            ':fotografia' =>
            $dados['fotografia'],

            ':observacoes' =>
            $dados['observacoes'],

            ':status_id' =>
            $dados['status_id'],

            ':criado_por' =>
            $usuarioId,

            ':atualizado_por' =>
            $usuarioId
        ]);

        $funcionarioId =
            (int)$this->pdo
                ->lastInsertId();


        $this->guardarDadosFiscais(
            $funcionarioId,
            $dados
        );


        return $funcionarioId;
    }

    // ==================================================
    // EDITAR
    // ==================================================

    public function editar(
        int $empresaId,
        int $usuarioId,
        int $funcionarioId,
        array $dados
    ): void {

        $this->validarEmpresa(
            $empresaId
        );

        $this->validarUsuario(
            $usuarioId
        );

        $this->validarId(
            $funcionarioId
        );

        $this->buscar(
            $empresaId,
            $funcionarioId
        );

        $dados =
            $this->normalizarDados(
                $dados
            );

        $this->validarRelacionamentos(
            $empresaId,
            $dados
        );

        $this->validarDuplicados(
            $empresaId,
            $dados,
            $funcionarioId
        );

        $stmt = $this->pdo->prepare("
            UPDATE funcionarios

            SET
                filial_id =
                    :filial_id,

                departamento_id =
                    :departamento_id,

                cargo_id =
                    :cargo_id,

                horario_trabalho_id =
                    :horario_trabalho_id,

                numero_funcionario =
                    :numero_funcionario,

                primeiro_nome =
                    :primeiro_nome,

                apelido =
                    :apelido,

                nome_completo =
                    :nome_completo,

                sexo =
                    :sexo,

                data_nascimento =
                    :data_nascimento,

                estado_civil =
                    :estado_civil,

                nacionalidade =
                    :nacionalidade,

                naturalidade =
                    :naturalidade,

                nuit =
                    :nuit,

                numero_bi =
                    :numero_bi,

                data_emissao_bi =
                    :data_emissao_bi,

                data_validade_bi =
                    :data_validade_bi,

                entidade_emissora_bi =
                    :entidade_emissora_bi,

                numero_inss =
                    :numero_inss,

                email_pessoal =
                    :email_pessoal,

                email_profissional =
                    :email_profissional,

                telefone_principal =
                    :telefone_principal,

                telefone_alternativo =
                    :telefone_alternativo,

                endereco =
                    :endereco,

                provincia =
                    :provincia,

                distrito =
                    :distrito,

                cidade =
                    :cidade,

                bairro =
                    :bairro,

                contacto_emergencia_nome =
                    :contacto_emergencia_nome,

                contacto_emergencia_parentesco =
                    :contacto_emergencia_parentesco,

                contacto_emergencia_telefone =
                    :contacto_emergencia_telefone,

                data_admissao =
                    :data_admissao,

                data_desligamento =
                    :data_desligamento,

                motivo_desligamento =
                    :motivo_desligamento,

                tipo_vinculo =
                    :tipo_vinculo,

                salario_base =
                    :salario_base,

                moeda =
                    :moeda,

                numero_conta_bancaria =
                    :numero_conta_bancaria,

                nib =
                    :nib,

                banco_nome =
                    :banco_nome,

                fotografia =
                    :fotografia,

                observacoes =
                    :observacoes,

                status_id =
                    :status_id,

                atualizado_por =
                    :atualizado_por

            WHERE id =
                :funcionario_id

              AND empresa_id =
                :empresa_id
        ");

        $stmt->execute([
            ':filial_id' =>
            $dados['filial_id'],

            ':departamento_id' =>
            $dados['departamento_id'],

            ':cargo_id' =>
            $dados['cargo_id'],

            ':horario_trabalho_id' =>
            $dados['horario_trabalho_id'],

            ':numero_funcionario' =>
            $dados['numero_funcionario'],

            ':primeiro_nome' =>
            $dados['primeiro_nome'],

            ':apelido' =>
            $dados['apelido'],

            ':nome_completo' =>
            $dados['nome_completo'],

            ':sexo' =>
            $dados['sexo'],

            ':data_nascimento' =>
            $dados['data_nascimento'],

            ':estado_civil' =>
            $dados['estado_civil'],

            ':nacionalidade' =>
            $dados['nacionalidade'],

            ':naturalidade' =>
            $dados['naturalidade'],

            ':nuit' =>
            $dados['nuit'],

            ':numero_bi' =>
            $dados['numero_bi'],

            ':data_emissao_bi' =>
            $dados['data_emissao_bi'],

            ':data_validade_bi' =>
            $dados['data_validade_bi'],

            ':entidade_emissora_bi' =>
            $dados['entidade_emissora_bi'],

            ':numero_inss' =>
            $dados['numero_inss'],

            ':email_pessoal' =>
            $dados['email_pessoal'],

            ':email_profissional' =>
            $dados['email_profissional'],

            ':telefone_principal' =>
            $dados['telefone_principal'],

            ':telefone_alternativo' =>
            $dados['telefone_alternativo'],

            ':endereco' =>
            $dados['endereco'],

            ':provincia' =>
            $dados['provincia'],

            ':distrito' =>
            $dados['distrito'],

            ':cidade' =>
            $dados['cidade'],

            ':bairro' =>
            $dados['bairro'],

            ':contacto_emergencia_nome' =>
            $dados['contacto_emergencia_nome'],

            ':contacto_emergencia_parentesco' =>
            $dados['contacto_emergencia_parentesco'],

            ':contacto_emergencia_telefone' =>
            $dados['contacto_emergencia_telefone'],

            ':data_admissao' =>
            $dados['data_admissao'],

            ':data_desligamento' =>
            $dados['data_desligamento'],

            ':motivo_desligamento' =>
            $dados['motivo_desligamento'],

            ':tipo_vinculo' =>
            $dados['tipo_vinculo'],

            ':salario_base' =>
            $dados['salario_base'],

            ':moeda' =>
            $dados['moeda'],

            ':numero_conta_bancaria' =>
            $dados['numero_conta_bancaria'],

            ':nib' =>
            $dados['nib'],

            ':banco_nome' =>
            $dados['banco_nome'],

            ':fotografia' =>
            $dados['fotografia'],

            ':observacoes' =>
            $dados['observacoes'],

            ':status_id' =>
            $dados['status_id'],

            ':atualizado_por' =>
            $usuarioId,

            ':funcionario_id' =>
            $funcionarioId,

            ':empresa_id' =>
            $empresaId
        ]);

        $this->guardarDadosFiscais(
            $funcionarioId,
            $dados
        );
    }

    private function buscarDadosFiscais(
        int $funcionarioId
    ): array {

        $stmt =
            $this->pdo->prepare("
            SELECT
                numero_dependentes,
                residente_fiscal,
                nuit

            FROM funcionario_dados_fiscais

            WHERE funcionario_id =
                :funcionario_id

            LIMIT 1
        ");


        $stmt->execute([
            ':funcionario_id' =>
            $funcionarioId
        ]);


        $dados =
            $stmt->fetch(
                PDO::FETCH_ASSOC
            );


        return $dados ?: [
            'numero_dependentes' =>
            0,

            'residente_fiscal' =>
            1,

            'nuit' =>
            null
        ];
    }

    // ==================================================
    // ELIMINAR
    // ==================================================

    public function eliminar(
        int $empresaId,
        int $funcionarioId
    ): void {

        $this->validarEmpresa(
            $empresaId
        );

        $this->validarId(
            $funcionarioId
        );

        $this->buscar(
            $empresaId,
            $funcionarioId
        );

        $stmt = $this->pdo->prepare("
            DELETE FROM funcionarios

            WHERE id =
                :funcionario_id

              AND empresa_id =
                :empresa_id
        ");

        $stmt->execute([
            ':funcionario_id' =>
            $funcionarioId,

            ':empresa_id' =>
            $empresaId
        ]);

        if ($stmt->rowCount() !== 1) {
            throw new RuntimeException(
                'Não foi possível eliminar o funcionário.'
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
            'funcionarios' =>
            $this->listar(
                $empresaId
            ),

            'filiais' =>
            $this->listarFiliais(
                $empresaId
            ),

            'departamentos' =>
            $this->listarDepartamentos(
                $empresaId
            ),

            'cargos' =>
            $this->listarCargos(
                $empresaId
            ),

            'horarios' =>
            $this->listarHorarios(
                $empresaId
            ),

            'status' =>
            $this->listarStatus(),

            'tipos_vinculo' =>
            $this->listarTiposVinculo(),

            'sexos' =>
            $this->listarSexos(),

            'estados_civis' =>
            $this->listarEstadosCivis()
        ];
    }

    // ==================================================
    // NORMALIZAR
    // ==================================================

    private function normalizarDados(
        array $dados
    ): array {

        $numeroFuncionario =
            strtoupper(
                trim(
                    (string)(
                        $dados['numero_funcionario']
                        ?? ''
                    )
                )
            );

        $primeiroNome =
            trim(
                (string)(
                    $dados['primeiro_nome']
                    ?? ''
                )
            );

        $apelido =
            trim(
                (string)(
                    $dados['apelido']
                    ?? ''
                )
            );

        $nomeCompleto =
            trim(
                $primeiroNome
                    . ' '
                    . $apelido
            );

        $sexo =
            $this->enumOpcional(
                $dados['sexo']
                    ?? null,
                [
                    'MASCULINO',
                    'FEMININO',
                    'OUTRO'
                ],
                'sexo'
            );

        $estadoCivil =
            $this->enumOpcional(
                $dados['estado_civil']
                    ?? null,
                [
                    'SOLTEIRO',
                    'CASADO',
                    'DIVORCIADO',
                    'VIUVO',
                    'UNIAO_FACTO',
                    'OUTRO'
                ],
                'estado civil'
            );

        $tipoVinculo =
            $this->enumObrigatorio(
                $dados['tipo_vinculo']
                    ?? 'CONTRATADO',
                [
                    'EFECTIVO',
                    'CONTRATADO',
                    'ESTAGIARIO',
                    'TEMPORARIO',
                    'PRESTADOR_SERVICO',
                    'OUTRO'
                ],
                'tipo de vínculo'
            );

        $dataNascimento =
            $this->dataOpcional(
                $dados['data_nascimento']
                    ?? null,
                'data de nascimento'
            );

        $dataAdmissao =
            $this->dataObrigatoria(
                $dados['data_admissao']
                    ?? null,
                'data de admissão'
            );

        $dataDesligamento =
            $this->dataOpcional(
                $dados['data_desligamento']
                    ?? null,
                'data de desligamento'
            );

        $dataEmissaoBi =
            $this->dataOpcional(
                $dados['data_emissao_bi']
                    ?? null,
                'data de emissão do BI'
            );

        $dataValidadeBi =
            $this->dataOpcional(
                $dados['data_validade_bi']
                    ?? null,
                'data de validade do BI'
            );

        $statusId =
            (int)(
                $dados['status_id']
                ?? 0
            );

        if ($numeroFuncionario === '') {
            throw new RuntimeException(
                'Informe o número do funcionário.'
            );
        }

        if (mb_strlen($numeroFuncionario) > 30) {
            throw new RuntimeException(
                'O número do funcionário não pode exceder 30 caracteres.'
            );
        }

        if ($primeiroNome === '') {
            throw new RuntimeException(
                'Informe o primeiro nome do funcionário.'
            );
        }

        if ($apelido === '') {
            throw new RuntimeException(
                'Informe o apelido do funcionário.'
            );
        }

        if (
            mb_strlen($primeiroNome) > 80
            || mb_strlen($apelido) > 80
        ) {
            throw new RuntimeException(
                'O nome ou apelido excede o tamanho permitido.'
            );
        }

        if (
            $dataNascimento !== null
            && $dataNascimento >= date('Y-m-d')
        ) {
            throw new RuntimeException(
                'A data de nascimento deve ser anterior à data atual.'
            );
        }

        if (
            $dataDesligamento !== null
            && $dataDesligamento < $dataAdmissao
        ) {
            throw new RuntimeException(
                'A data de desligamento não pode ser anterior à data de admissão.'
            );
        }

        if (
            $dataEmissaoBi !== null
            && $dataValidadeBi !== null
            && $dataValidadeBi < $dataEmissaoBi
        ) {
            throw new RuntimeException(
                'A validade do BI não pode ser anterior à data de emissão.'
            );
        }

        $emailPessoal =
            $this->emailOpcional(
                $dados['email_pessoal']
                    ?? null,
                'e-mail pessoal'
            );

        $emailProfissional =
            $this->emailOpcional(
                $dados['email_profissional']
                    ?? null,
                'e-mail profissional'
            );

        $salarioBase =
            $this->decimalNaoNegativo(
                $dados['salario_base']
                    ?? 0,
                'salário base'
            );

        $moeda =
            strtoupper(
                trim(
                    (string)(
                        $dados['moeda']
                        ?? 'MZN'
                    )
                )
            );

        if (
            !preg_match(
                '/^[A-Z]{3}$/',
                $moeda
            )
        ) {
            throw new RuntimeException(
                'A moeda deve possuir três letras.'
            );
        }

        if ($statusId <= 0) {
            $statusId =
                $this->statusAtivoId();
        }

        return [
            'filial_id' =>
            $this->inteiroOpcional(
                $dados['filial_id']
                    ?? null
            ),

            'departamento_id' =>
            $this->inteiroOpcional(
                $dados['departamento_id']
                    ?? null
            ),

            'cargo_id' =>
            $this->inteiroOpcional(
                $dados['cargo_id']
                    ?? null
            ),

            'horario_trabalho_id' =>
            $this->inteiroOpcional(
                $dados['horario_trabalho_id']
                    ?? null
            ),

            'numero_funcionario' =>
            $numeroFuncionario,

            'primeiro_nome' =>
            $primeiroNome,

            'apelido' =>
            $apelido,

            'nome_completo' =>
            $nomeCompleto,

            'sexo' =>
            $sexo,

            'data_nascimento' =>
            $dataNascimento,

            'estado_civil' =>
            $estadoCivil,

            'nacionalidade' =>
            $this->textoOpcional(
                $dados['nacionalidade']
                    ?? 'Moçambicana'
            )
                ?? 'Moçambicana',

            'naturalidade' =>
            $this->textoOpcional(
                $dados['naturalidade']
                    ?? null
            ),

            'numero_dependentes' =>
            max(
                0,
                (int)(
                    $dados['numero_dependentes']
                    ?? 0
                )
            ),

            'residente_fiscal' =>
            isset(
                $dados['residente_fiscal']
            )
                && (int)$dados['residente_fiscal'] === 0
                ? 0
                : 1,

            'nuit' =>
            $this->textoOpcional(
                $dados['nuit']
                    ?? null
            ),

            'numero_bi' =>
            $this->textoOpcional(
                $dados['numero_bi']
                    ?? null
            ),

            'data_emissao_bi' =>
            $dataEmissaoBi,

            'data_validade_bi' =>
            $dataValidadeBi,

            'entidade_emissora_bi' =>
            $this->textoOpcional(
                $dados['entidade_emissora_bi']
                    ?? null
            ),

            'numero_inss' =>
            $this->textoOpcional(
                $dados['numero_inss']
                    ?? null
            ),

            'email_pessoal' =>
            $emailPessoal,

            'email_profissional' =>
            $emailProfissional,

            'telefone_principal' =>
            $this->textoOpcional(
                $dados['telefone_principal']
                    ?? null
            ),

            'telefone_alternativo' =>
            $this->textoOpcional(
                $dados['telefone_alternativo']
                    ?? null
            ),

            'endereco' =>
            $this->textoOpcional(
                $dados['endereco']
                    ?? null
            ),

            'provincia' =>
            $this->textoOpcional(
                $dados['provincia']
                    ?? null
            ),

            'distrito' =>
            $this->textoOpcional(
                $dados['distrito']
                    ?? null
            ),

            'cidade' =>
            $this->textoOpcional(
                $dados['cidade']
                    ?? null
            ),

            'bairro' =>
            $this->textoOpcional(
                $dados['bairro']
                    ?? null
            ),

            'contacto_emergencia_nome' =>
            $this->textoOpcional(
                $dados['contacto_emergencia_nome']
                    ?? null
            ),

            'contacto_emergencia_parentesco' =>
            $this->textoOpcional(
                $dados['contacto_emergencia_parentesco']
                    ?? null
            ),

            'contacto_emergencia_telefone' =>
            $this->textoOpcional(
                $dados['contacto_emergencia_telefone']
                    ?? null
            ),

            'data_admissao' =>
            $dataAdmissao,

            'data_desligamento' =>
            $dataDesligamento,

            'motivo_desligamento' =>
            $this->textoOpcional(
                $dados['motivo_desligamento']
                    ?? null
            ),

            'tipo_vinculo' =>
            $tipoVinculo,

            'salario_base' =>
            $salarioBase,

            'moeda' =>
            $moeda,

            'numero_conta_bancaria' =>
            $this->textoOpcional(
                $dados['numero_conta_bancaria']
                    ?? null
            ),

            'nib' =>
            $this->textoOpcional(
                $dados['nib']
                    ?? null
            ),

            'banco_nome' =>
            $this->textoOpcional(
                $dados['banco_nome']
                    ?? null
            ),

            'fotografia' =>
            $this->textoOpcional(
                $dados['fotografia']
                    ?? null
            ),

            'observacoes' =>
            $this->textoOpcional(
                $dados['observacoes']
                    ?? null
            ),

            'status_id' =>
            $statusId
        ];
    }

    // ==================================================
    // RELACIONAMENTOS
    // ==================================================

    private function validarRelacionamentos(
        int $empresaId,
        array $dados
    ): void {

        if ($dados['filial_id'] !== null) {
            $this->validarRegistroEmpresa(
                'filiais',
                $dados['filial_id'],
                $empresaId,
                'Filial inválida.'
            );
        }

        if ($dados['departamento_id'] !== null) {
            $this->validarRegistroEmpresa(
                'departamentos',
                $dados['departamento_id'],
                $empresaId,
                'Departamento inválido.'
            );
        }

        if ($dados['cargo_id'] !== null) {
            $this->validarRegistroEmpresa(
                'cargos',
                $dados['cargo_id'],
                $empresaId,
                'Cargo inválido.'
            );
        }

        if ($dados['horario_trabalho_id'] !== null) {
            $this->validarRegistroEmpresa(
                'horarios_trabalho',
                $dados['horario_trabalho_id'],
                $empresaId,
                'Horário de trabalho inválido.'
            );
        }

        $this->validarStatus(
            $dados['status_id']
        );
    }

    private function validarRegistroEmpresa(
        string $tabela,
        int $registroId,
        int $empresaId,
        string $mensagem
    ): void {

        $tabelasPermitidas = [
            'filiais',
            'departamentos',
            'cargos',
            'horarios_trabalho'
        ];

        if (
            !in_array(
                $tabela,
                $tabelasPermitidas,
                true
            )
        ) {
            throw new RuntimeException(
                'Tabela de validação inválida.'
            );
        }

        $stmt = $this->pdo->prepare("
            SELECT id

            FROM {$tabela}

            WHERE id =
                :registro_id

              AND empresa_id =
                :empresa_id

            LIMIT 1
        ");

        $stmt->execute([
            ':registro_id' =>
            $registroId,

            ':empresa_id' =>
            $empresaId
        ]);

        if (!$stmt->fetchColumn()) {
            throw new RuntimeException(
                $mensagem
            );
        }
    }

    // ==================================================
    // DUPLICADOS
    // ==================================================

    private function validarDuplicados(
        int $empresaId,
        array $dados,
        ?int $ignorarId = null
    ): void {

        $this->validarCampoDuplicado(
            $empresaId,
            'numero_funcionario',
            $dados['numero_funcionario'],
            'Já existe um funcionário com este número.',
            $ignorarId
        );

        if ($dados['nuit'] !== null) {
            $this->validarCampoDuplicado(
                $empresaId,
                'nuit',
                $dados['nuit'],
                'Já existe um funcionário com este NUIT.',
                $ignorarId
            );
        }

        if ($dados['numero_bi'] !== null) {
            $this->validarCampoDuplicado(
                $empresaId,
                'numero_bi',
                $dados['numero_bi'],
                'Já existe um funcionário com este número de BI.',
                $ignorarId
            );
        }
    }

    private function validarCampoDuplicado(
        int $empresaId,
        string $campo,
        string $valor,
        string $mensagem,
        ?int $ignorarId = null
    ): void {

        $camposPermitidos = [
            'numero_funcionario',
            'nuit',
            'numero_bi'
        ];

        if (
            !in_array(
                $campo,
                $camposPermitidos,
                true
            )
        ) {
            throw new RuntimeException(
                'Campo de validação inválido.'
            );
        }

        $sql = "
            SELECT id

            FROM funcionarios

            WHERE empresa_id =
                :empresa_id

              AND {$campo} =
                :valor
        ";

        $params = [
            ':empresa_id' =>
            $empresaId,

            ':valor' =>
            $valor
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
                $mensagem
            );
        }
    }

    // ==================================================
    // LISTAS AUXILIARES
    // ==================================================

    private function listarFiliais(
        int $empresaId
    ): array {

        $stmt = $this->pdo->prepare("
            SELECT
                id,
                codigo,
                nome

            FROM filiais

            WHERE empresa_id =
                :empresa_id

            ORDER BY nome ASC
        ");

        $stmt->execute([
            ':empresa_id' =>
            $empresaId
        ]);

        return $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );
    }

    private function listarDepartamentos(
        int $empresaId
    ): array {

        $stmt = $this->pdo->prepare("
            SELECT
                departamento.id,
                departamento.codigo,
                departamento.nome

            FROM departamentos departamento

            INNER JOIN status
                ON status.id =
                    departamento.status_id

               AND status.tipo =
                    'departamento'

            WHERE departamento.empresa_id =
                :empresa_id

              AND LOWER(status.nome) IN
              (
                  'activo',
                  'ativo',
                  'activa',
                  'ativa'
              )

            ORDER BY departamento.nome ASC
        ");

        $stmt->execute([
            ':empresa_id' =>
            $empresaId
        ]);

        return $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );
    }

    private function listarCargos(
        int $empresaId
    ): array {

        $stmt = $this->pdo->prepare("
            SELECT
                cargo.id,
                cargo.codigo,
                cargo.nome,
                cargo.departamento_id

            FROM cargos cargo

            INNER JOIN status
                ON status.id =
                    cargo.status_id

               AND status.tipo =
                    'cargo'

            WHERE cargo.empresa_id =
                :empresa_id

              AND LOWER(status.nome) IN
              (
                  'activo',
                  'ativo',
                  'activa',
                  'ativa'
              )

            ORDER BY cargo.nome ASC
        ");

        $stmt->execute([
            ':empresa_id' =>
            $empresaId
        ]);

        return $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );
    }

    private function listarHorarios(
        int $empresaId
    ): array {

        $stmt = $this->pdo->prepare("
            SELECT
                horario.id,
                horario.codigo,
                horario.nome,
                horario.hora_entrada,
                horario.hora_saida

            FROM horarios_trabalho horario

            INNER JOIN status
                ON status.id =
                    horario.status_id

               AND status.tipo =
                    'horario_trabalho'

            WHERE horario.empresa_id =
                :empresa_id

              AND LOWER(status.nome) IN
              (
                  'activo',
                  'ativo',
                  'activa',
                  'ativa'
              )

            ORDER BY horario.nome ASC
        ");

        $stmt->execute([
            ':empresa_id' =>
            $empresaId
        ]);

        return $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );
    }

    private function listarStatus(): array
    {
        $stmt = $this->pdo->query("
            SELECT
                id,
                nome,
                descricao

            FROM status

            WHERE tipo =
                'funcionario'

            ORDER BY id ASC
        ");

        return $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );
    }

    private function listarTiposVinculo(): array
    {
        return [
            [
                'codigo' => 'EFECTIVO',
                'nome' => 'Efetivo'
            ],
            [
                'codigo' => 'CONTRATADO',
                'nome' => 'Contratado'
            ],
            [
                'codigo' => 'ESTAGIARIO',
                'nome' => 'Estagiário'
            ],
            [
                'codigo' => 'TEMPORARIO',
                'nome' => 'Temporário'
            ],
            [
                'codigo' => 'PRESTADOR_SERVICO',
                'nome' => 'Prestador de Serviço'
            ],
            [
                'codigo' => 'OUTRO',
                'nome' => 'Outro'
            ]
        ];
    }

    private function listarSexos(): array
    {
        return [
            [
                'codigo' => 'MASCULINO',
                'nome' => 'Masculino'
            ],
            [
                'codigo' => 'FEMININO',
                'nome' => 'Feminino'
            ],
            [
                'codigo' => 'OUTRO',
                'nome' => 'Outro'
            ]
        ];
    }

    private function listarEstadosCivis(): array
    {
        return [
            [
                'codigo' => 'SOLTEIRO',
                'nome' => 'Solteiro'
            ],
            [
                'codigo' => 'CASADO',
                'nome' => 'Casado'
            ],
            [
                'codigo' => 'DIVORCIADO',
                'nome' => 'Divorciado'
            ],
            [
                'codigo' => 'VIUVO',
                'nome' => 'Viúvo'
            ],
            [
                'codigo' => 'UNIAO_FACTO',
                'nome' => 'União de Facto'
            ],
            [
                'codigo' => 'OUTRO',
                'nome' => 'Outro'
            ]
        ];
    }

    // ==================================================
    // STATUS
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
                'funcionario'

            LIMIT 1
        ");

        $stmt->execute([
            ':status_id' =>
            $statusId
        ]);

        if (!$stmt->fetchColumn()) {
            throw new RuntimeException(
                'Estado do funcionário inválido.'
            );
        }
    }

    private function statusAtivoId(): int
    {
        $stmt = $this->pdo->query("
            SELECT id

            FROM status

            WHERE tipo =
                'funcionario'

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
                'O estado ativo do funcionário não está configurado.'
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
                'Empresa inválida para a gestão de funcionários.'
            );
        }
    }

    private function validarId(
        int $funcionarioId
    ): void {

        if ($funcionarioId <= 0) {
            throw new RuntimeException(
                'Funcionário inválido.'
            );
        }
    }

    // ==================================================
    // HELPERS
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

        return $this->validarData(
            $data,
            $campo
        );
    }

    private function dataOpcional(
        mixed $valor,
        string $campo
    ): ?string {

        if (
            $valor === null
            || trim((string)$valor) === ''
        ) {
            return null;
        }

        return $this->validarData(
            trim((string)$valor),
            $campo
        );
    }

    private function validarData(
        string $data,
        string $campo
    ): string {

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

    private function emailOpcional(
        mixed $valor,
        string $campo
    ): ?string {

        $email =
            trim(
                (string)(
                    $valor ?? ''
                )
            );

        if ($email === '') {
            return null;
        }

        if (
            filter_var(
                $email,
                FILTER_VALIDATE_EMAIL
            ) === false
        ) {
            throw new RuntimeException(
                'O ' . $campo . ' é inválido.'
            );
        }

        return strtolower(
            $email
        );
    }

    private function decimalNaoNegativo(
        mixed $valor,
        string $campo
    ): string {

        $texto =
            str_replace(
                ',',
                '.',
                trim(
                    (string)(
                        $valor ?? '0'
                    )
                )
            );

        if (
            $texto === ''
            || !is_numeric($texto)
        ) {
            throw new RuntimeException(
                'O ' . $campo . ' é inválido.'
            );
        }

        $numero =
            (float)$texto;

        if ($numero < 0) {
            throw new RuntimeException(
                'O ' . $campo . ' não pode ser negativo.'
            );
        }

        return number_format(
            $numero,
            2,
            '.',
            ''
        );
    }

    private function enumObrigatorio(
        mixed $valor,
        array $permitidos,
        string $campo
    ): string {

        $texto =
            strtoupper(
                trim(
                    (string)(
                        $valor ?? ''
                    )
                )
            );

        if (
            !in_array(
                $texto,
                $permitidos,
                true
            )
        ) {
            throw new RuntimeException(
                'O ' . $campo . ' é inválido.'
            );
        }

        return $texto;
    }

    private function enumOpcional(
        mixed $valor,
        array $permitidos,
        string $campo
    ): ?string {

        if (
            $valor === null
            || trim((string)$valor) === ''
        ) {
            return null;
        }

        return $this->enumObrigatorio(
            $valor,
            $permitidos,
            $campo
        );
    }

    private function inteiroOpcional(
        mixed $valor
    ): ?int {

        if (
            $valor === null
            || trim((string)$valor) === ''
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
}
