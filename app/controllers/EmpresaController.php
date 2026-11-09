<?php

declare(strict_types=1);

final class EmpresaController
{
    private Empresa $model;

    public function __construct()
    {
        $this->model =
            new Empresa(
                Database::conn()
            );
    }

    /*
    |--------------------------------------------------------------------------
    | Listar
    |--------------------------------------------------------------------------
    */

    public function listar(): array
    {
        try {
            return [
                'sucesso' => true,
                'dados' =>
                $this->model->listar()
            ];
        } catch (Throwable $e) {
            return $this->erro($e);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Buscar
    |--------------------------------------------------------------------------
    */

    public function buscar(): array
    {
        try {
            $id =
                $this->inteiroObrigatorio(
                    $_GET['id']
                        ?? $_POST['id']
                        ?? null,
                    'Empresa inválida.'
                );

            $empresa =
                $this->model->buscar(
                    $id
                );

            if (!$empresa) {
                return [
                    'sucesso' => false,
                    'mensagem' =>
                    'Empresa não encontrada.'
                ];
            }

            return [
                'sucesso' => true,
                'dados' => $empresa
            ];
        } catch (Throwable $e) {
            return $this->erro($e);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Estados
    |--------------------------------------------------------------------------
    */

    public function status(): array
    {
        try {
            return [
                'sucesso' => true,
                'dados' =>
                $this->model
                    ->listarStatus()
            ];
        } catch (Throwable $e) {
            return $this->erro($e);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Guardar
    |--------------------------------------------------------------------------
    */

    public function salvar(
        array $entrada,
        ?array $arquivoLogo = null
    ): array {
        try {
            $id =
                $this->inteiroOpcional(
                    $entrada['id']
                        ?? null
                );

            $empresaAtual =
                $id !== null
                ? $this->model->buscar($id)
                : null;

            if (
                $id !== null
                && !$empresaAtual
            ) {
                return [
                    'sucesso' => false,
                    'mensagem' =>
                    'Empresa não encontrada.'
                ];
            }

            $nome =
                $this->textoObrigatorio(
                    $entrada['nome']
                        ?? null,
                    'Informe o nome da empresa.',
                    150
                );

            $nuit =
                $this->textoOpcional(
                    $entrada['nuit']
                        ?? null,
                    30
                );

            if (
                $nuit !== null
                && $this->model->nuitExiste(
                    $nuit,
                    $id
                )
            ) {
                return [
                    'sucesso' => false,
                    'mensagem' =>
                    'Já existe uma empresa com este NUIT.'
                ];
            }

            $statusId =
                $this->inteiroObrigatorio(
                    $entrada['status_id']
                        ?? null,
                    'Selecione o estado da empresa.'
                );

            if (
                !$this->model->statusExiste(
                    $statusId
                )
            ) {
                return [
                    'sucesso' => false,
                    'mensagem' =>
                    'O estado selecionado não pertence às empresas.'
                ];
            }

            $tipoEmpresa =
                $this->tipoEmpresa(
                    $entrada['tipo_empresa']
                        ?? null
                );

            $moeda =
                $this->moeda(
                    $entrada['moeda']
                        ?? 'MZN'
                );

            $logoAtual =
                $empresaAtual['logo']
                ?? $this->textoOpcional(
                    $entrada['logo_atual']
                        ?? null,
                    255
                );

            $novoLogo =
                $this->processarLogo(
                    $arquivoLogo
                );

            $logo =
                $novoLogo
                ?? $logoAtual;

            $dados = [
                'nome' =>
                $nome,

                'nome_fantasia' =>
                $this->textoOpcional(
                    $entrada['nome_fantasia']
                        ?? null,
                    150
                ),

                'nuit' =>
                $nuit,

                'email' =>
                $this->emailOpcional(
                    $entrada['email']
                        ?? null
                ),

                'website' =>
                $this->textoOpcional(
                    $entrada['website']
                        ?? null,
                    190
                ),

                'telefone' =>
                $this->textoOpcional(
                    $entrada['telefone']
                        ?? null,
                    30
                ),

                'logradouro' =>
                $this->textoOpcional(
                    $entrada['logradouro']
                        ?? null,
                    255
                ),

                'provincia' =>
                $this->textoOpcional(
                    $entrada['provincia']
                        ?? null,
                    100
                ),

                'cidade' =>
                $this->textoOpcional(
                    $entrada['cidade']
                        ?? null,
                    100
                ),

                'codigo_postal' =>
                $this->textoOpcional(
                    $entrada['codigo_postal']
                        ?? null,
                    20
                ),

                'logo' =>
                $logo,

                'status_id' =>
                $statusId,

                'tipo_empresa' =>
                $tipoEmpresa,

                'ramo_actividade' =>
                $this->textoOpcional(
                    $entrada['ramo_actividade']
                        ?? null,
                    150
                ),

                'contacto_principal' =>
                $this->textoOpcional(
                    $entrada['contacto_principal']
                        ?? null,
                    150
                ),

                'slogan' =>
                $this->textoOpcional(
                    $entrada['slogan']
                        ?? null,
                    255
                ),

                'descricao' =>
                $this->textoOpcionalSemLimite(
                    $entrada['descricao']
                        ?? null
                ),

                'pais' =>
                $this->textoOpcional(
                    $entrada['pais']
                        ?? 'Moçambique',
                    100
                ) ?? 'Moçambique',

                'moeda' =>
                $moeda,

                'fuso_horario' =>
                $this->textoOpcional(
                    $entrada['fuso_horario']
                        ?? 'Africa/Maputo',
                    50
                ) ?? 'Africa/Maputo'
            ];

            if ($id !== null) {
                $this->model->atualizar(
                    $id,
                    $dados
                );

                if (
                    $novoLogo !== null
                    && !empty($empresaAtual['logo'])
                    && $empresaAtual['logo']
                    !== $novoLogo
                ) {
                    $this->eliminarLogoFisico(
                        (string) $empresaAtual['logo']
                    );
                }

                return [
                    'sucesso' => true,
                    'mensagem' =>
                    'Empresa atualizada com sucesso.',
                    'dados' => [
                        'id' => $id
                    ]
                ];
            }

            $novoId =
                $this->model->criar(
                    $dados
                );

            return [
                'sucesso' => true,
                'mensagem' =>
                'Empresa criada com sucesso.',
                'dados' => [
                    'id' => $novoId
                ]
            ];
        } catch (PDOException $e) {
            return $this->erroBanco(
                $e
            );
        } catch (Throwable $e) {
            return $this->erro($e);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Eliminar
    |--------------------------------------------------------------------------
    */

    public function eliminar(): array
    {
        try {
            $id =
                $this->inteiroObrigatorio(
                    $_POST['id']
                        ?? null,
                    'Empresa inválida.'
                );

            $empresa =
                $this->model->buscar(
                    $id
                );

            if (!$empresa) {
                return [
                    'sucesso' => false,
                    'mensagem' =>
                    'Empresa não encontrada.'
                ];
            }

            $this->model->eliminar(
                $id
            );

            if (!empty($empresa['logo'])) {
                $this->eliminarLogoFisico(
                    (string) $empresa['logo']
                );
            }

            return [
                'sucesso' => true,
                'mensagem' =>
                'Empresa eliminada com sucesso.'
            ];
        } catch (PDOException $e) {
            if (
                (string) $e->getCode()
                === '23000'
            ) {
                return [
                    'sucesso' => false,
                    'mensagem' =>
                    'A empresa possui registos associados e não pode ser eliminada.'
                ];
            }

            return $this->erroBanco($e);
        } catch (Throwable $e) {
            return $this->erro($e);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Upload de logo
    |--------------------------------------------------------------------------
    */

    private function processarLogo(
        ?array $arquivo
    ): ?string {
        if (
            !$arquivo
            || ($arquivo['error']
                ?? UPLOAD_ERR_NO_FILE)
            === UPLOAD_ERR_NO_FILE
        ) {
            return null;
        }

        if (
            ($arquivo['error']
                ?? UPLOAD_ERR_NO_FILE)
            !== UPLOAD_ERR_OK
        ) {
            throw new RuntimeException(
                'Ocorreu um erro ao enviar o logo.'
            );
        }

        $tamanho =
            (int) (
                $arquivo['size']
                ?? 0
            );

        if (
            $tamanho <= 0
            || $tamanho > 5 * 1024 * 1024
        ) {
            throw new InvalidArgumentException(
                'O logo deve possuir no máximo 5 MB.'
            );
        }

        $mime =
            (new finfo(
                FILEINFO_MIME_TYPE
            ))->file(
                (string) $arquivo['tmp_name']
            );

        $extensoes = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp'
        ];

        if (
            !isset($extensoes[$mime])
        ) {
            throw new InvalidArgumentException(
                'O formato do logo não é permitido.'
            );
        }

        $pasta =
            dirname(__DIR__, 2)
            . '/public/uploads/empresas';

        if (
            !is_dir($pasta)
            && !mkdir(
                $pasta,
                0775,
                true
            )
            && !is_dir($pasta)
        ) {
            throw new RuntimeException(
                'Não foi possível criar a pasta de logos.'
            );
        }

        $nome =
            'empresa_'
            . bin2hex(random_bytes(8))
            . '.'
            . $extensoes[$mime];

        $destino =
            $pasta
            . DIRECTORY_SEPARATOR
            . $nome;

        if (
            !move_uploaded_file(
                (string) $arquivo['tmp_name'],
                $destino
            )
        ) {
            throw new RuntimeException(
                'Não foi possível guardar o logo.'
            );
        }

        return $nome;
    }

    private function eliminarLogoFisico(
        string $nome
    ): void {
        $nome =
            basename($nome);

        if ($nome === '') {
            return;
        }

        $caminho =
            dirname(__DIR__, 2)
            . '/public/uploads/empresas/'
            . $nome;

        if (
            is_file($caminho)
        ) {
            @unlink($caminho);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Validações
    |--------------------------------------------------------------------------
    */

    private function inteiroObrigatorio(
        mixed $valor,
        string $mensagem
    ): int {
        $numero =
            filter_var(
                $valor,
                FILTER_VALIDATE_INT
            );

        if (
            $numero === false
            || $numero <= 0
        ) {
            throw new InvalidArgumentException(
                $mensagem
            );
        }

        return (int) $numero;
    }

    private function inteiroOpcional(
        mixed $valor
    ): ?int {
        if (
            $valor === null
            || $valor === ''
        ) {
            return null;
        }

        return $this->inteiroObrigatorio(
            $valor,
            'Identificador inválido.'
        );
    }

    private function textoObrigatorio(
        mixed $valor,
        string $mensagem,
        int $limite
    ): string {
        $texto =
            trim(
                (string) $valor
            );

        if ($texto === '') {
            throw new InvalidArgumentException(
                $mensagem
            );
        }

        if (
            mb_strlen($texto)
            > $limite
        ) {
            throw new InvalidArgumentException(
                'O valor informado excede o limite permitido.'
            );
        }

        return $texto;
    }

    private function textoOpcional(
        mixed $valor,
        int $limite
    ): ?string {
        $texto =
            trim(
                (string) $valor
            );

        if ($texto === '') {
            return null;
        }

        if (
            mb_strlen($texto)
            > $limite
        ) {
            throw new InvalidArgumentException(
                'O valor informado excede o limite permitido.'
            );
        }

        return $texto;
    }

    private function textoOpcionalSemLimite(
        mixed $valor
    ): ?string {
        $texto =
            trim(
                (string) $valor
            );

        return $texto === ''
            ? null
            : $texto;
    }

    private function emailOpcional(
        mixed $valor
    ): ?string {
        $email =
            trim(
                (string) $valor
            );

        if ($email === '') {
            return null;
        }

        if (
            !filter_var(
                $email,
                FILTER_VALIDATE_EMAIL
            )
        ) {
            throw new InvalidArgumentException(
                'Informe um email válido.'
            );
        }

        return mb_strtolower($email);
    }

    private function tipoEmpresa(
        mixed $valor
    ): ?string {
        $tipo =
            trim(
                (string) $valor
            );

        if ($tipo === '') {
            return null;
        }

        $permitidos = [
            'Pequena',
            'Média',
            'Grande'
        ];

        if (
            !in_array(
                $tipo,
                $permitidos,
                true
            )
        ) {
            throw new InvalidArgumentException(
                'O tipo de empresa é inválido.'
            );
        }

        return $tipo;
    }

    private function moeda(
        mixed $valor
    ): string {
        $moeda =
            strtoupper(
                trim(
                    (string) $valor
                )
            );

        $permitidas = [
            'MZN',
            'USD',
            'ZAR',
            'EUR'
        ];

        if (
            !in_array(
                $moeda,
                $permitidas,
                true
            )
        ) {
            throw new InvalidArgumentException(
                'A moeda selecionada é inválida.'
            );
        }

        return $moeda;
    }

    /*
    |--------------------------------------------------------------------------
    | Erros
    |--------------------------------------------------------------------------
    */

    private function erroBanco(
        PDOException $e
    ): array {
        error_log(
            sprintf(
                '[EmpresaController] %s em %s:%d',
                $e->getMessage(),
                $e->getFile(),
                $e->getLine()
            )
        );

        $mensagem =
            mb_strtolower(
                $e->getMessage()
            );

        if (
            str_contains(
                $mensagem,
                'nuit'
            )
            || str_contains(
                $mensagem,
                'duplicate'
            )
        ) {
            return [
                'sucesso' => false,
                'mensagem' =>
                'Já existe uma empresa com este NUIT.'
            ];
        }

        return [
            'sucesso' => false,
            'mensagem' =>
            'Não foi possível guardar os dados da empresa.'
        ];
    }

    private function erro(
        Throwable $e
    ): array {
        error_log(
            sprintf(
                '[EmpresaController] %s em %s:%d',
                $e->getMessage(),
                $e->getFile(),
                $e->getLine()
            )
        );

        return [
            'sucesso' => false,
            'mensagem' =>
            $e instanceof InvalidArgumentException
                || $e instanceof RuntimeException
                ? $e->getMessage()
                : 'Ocorreu um erro interno ao processar a empresa.'
        ];
    }
}
