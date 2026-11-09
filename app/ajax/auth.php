<?php

declare(strict_types=1);

header(
    'Content-Type: application/json; charset=utf-8'
);

require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../models/Usuario.php';

function respond(
    bool $ok,
    array $payload = [],
    int $code = 200
): never {
    http_response_code($code);

    echo json_encode(
        ['ok' => $ok] + $payload,
        JSON_UNESCAPED_UNICODE
        | JSON_UNESCAPED_SLASHES
    );

    exit;
}

$action = strtolower(
    trim(
        (string)($_GET['action'] ?? '')
    )
);

try {
    /*
     * O model Usuario trabalha com instância.
     * Criamos uma única instância para todas as ações.
     */
    $usuarioModel = new Usuario();

    switch ($action) {
        /*
         * =====================================================
         * LOGIN
         * =====================================================
         */
        case 'login':
            if (
                strtoupper(
                    $_SERVER['REQUEST_METHOD']
                    ?? 'GET'
                ) !== 'POST'
            ) {
                respond(
                    false,
                    ['message' => 'Método inválido.'],
                    405
                );
            }

            $login = trim(
                (string)($_POST['login'] ?? '')
            );

            $senha =
                (string)($_POST['senha'] ?? '');

            if ($login === '' || $senha === '') {
                respond(
                    false,
                    [
                        'message' =>
                            'Preencha o utilizador/email e a palavra-passe.'
                    ],
                    400
                );
            }

            $usuario =
                $usuarioModel->buscarPorLogin(
                    $login
                );

            if (!$usuario) {
                respond(
                    false,
                    ['message' => 'Credenciais inválidas.'],
                    401
                );
            }

            /*
             * Mantém esta validação apenas se o estado ativo
             * dos utilizadores realmente tiver ID 1.
             */
            if ((int)$usuario['status_id'] !== 1) {
                respond(
                    false,
                    ['message' => 'Conta inativa.'],
                    403
                );
            }

            $senhaHash =
                (string)($usuario['senha_hash'] ?? '');

            if (
                $senhaHash === ''
                || !password_verify(
                    $senha,
                    $senhaHash
                )
            ) {
                respond(
                    false,
                    ['message' => 'Credenciais inválidas.'],
                    401
                );
            }

            Auth::login($usuario);

            $perfilId =
                (int)$usuario['perfil_id'];

            $redirect =
                '/erpplus/index.php?page=dashboard';

            switch ($perfilId) {
                case 1:
                    $redirect =
                        '/erpplus/index.php?page=dashboard';
                    break;

                case 2:
                    /*
                     * Confirma se esta rota realmente existe.
                     * O perfil 2 é administrador_empresa,
                     * não vendedor.
                     */
                    $redirect =
                        '/erpplus/index.php?page=dashboard';
                    break;

                case 3:
                    $redirect =
                        '/erpplus/index.php?page=dashboard';
                    break;

                case 4:
                    $redirect =
                        '/erpplus/index.php?page=marketplace';
                    break;

                case 7:
                    $redirect =
                        '/erpplus/index.php?page=dashboard_rh';
                    break;

                case 8:
                    $redirect =
                        '/erpplus/index.php?page=indicadores_financeiros';
                    break;

                case 9:
                    $redirect =
                        '/erpplus/index.php?page=folhas_salariais';
                    break;

                case 10:
                    $redirect =
                        '/erpplus/index.php?page=indicadores_contabilisticos';
                    break;
            }

            respond(
                true,
                [
                    'message' =>
                        'Login efectuado com sucesso.',

                    'redirect' =>
                        $redirect
                ]
            );

        /*
         * =====================================================
         * REGISTO
         * =====================================================
         */
        case 'register':
            if (
                strtoupper(
                    $_SERVER['REQUEST_METHOD']
                    ?? 'GET'
                ) !== 'POST'
            ) {
                respond(
                    false,
                    ['message' => 'Método inválido.'],
                    405
                );
            }

            $username = mb_strtolower(
                trim(
                    (string)($_POST['username'] ?? '')
                )
            );

            $nome = trim(
                (string)($_POST['nome'] ?? '')
            );

            $email = mb_strtolower(
                trim(
                    (string)($_POST['email'] ?? '')
                )
            );

            $telefone = trim(
                (string)($_POST['telefone'] ?? '')
            );

            $senha =
                (string)($_POST['senha'] ?? '');

            $confirmar =
                (string)(
                    $_POST['confirmar_senha']
                    ?? ''
                );

            if (
                $username === ''
                || $nome === ''
                || $email === ''
                || $senha === ''
                || $confirmar === ''
            ) {
                respond(
                    false,
                    [
                        'message' =>
                            'Preencha todos os campos obrigatórios.'
                    ],
                    400
                );
            }

            if (
                !filter_var(
                    $email,
                    FILTER_VALIDATE_EMAIL
                )
            ) {
                respond(
                    false,
                    ['message' => 'Email inválido.'],
                    400
                );
            }

            if ($senha !== $confirmar) {
                respond(
                    false,
                    [
                        'message' =>
                            'As palavras-passe não coincidem.'
                    ],
                    400
                );
            }

            if (mb_strlen($senha) < 6) {
                respond(
                    false,
                    [
                        'message' =>
                            'A palavra-passe deve ter pelo menos 6 caracteres.'
                    ],
                    400
                );
            }

            /*
             * No model atual existem emailExiste()
             * e usernameExiste().
             */
            if (
                $usuarioModel->emailExiste(
                    $email
                )
            ) {
                respond(
                    false,
                    [
                        'message' =>
                            'Este email já está registado.'
                    ],
                    409
                );
            }

            if (
                $usuarioModel->usernameExiste(
                    $username
                )
            ) {
                respond(
                    false,
                    [
                        'message' =>
                            'Este nome de utilizador já existe.'
                    ],
                    409
                );
            }

            if (
                $telefone !== ''
                && $usuarioModel->telefoneExiste(
                    $telefone
                )
            ) {
                respond(
                    false,
                    [
                        'message' =>
                            'Este telefone já está registado.'
                    ],
                    409
                );
            }

            /*
             * O método criar() do model:
             * - espera a chave "senha";
             * - gera o hash internamente;
             * - espera empresa_id e filial_id.
             */
            $id = $usuarioModel->criar([
                'perfil_id' => 4,
                'empresa_id' => null,
                'filial_id' => null,
                'status_id' => 1,

                'username' => $username,
                'nome' => $nome,
                'email' => $email,

                'telefone' =>
                    $telefone !== ''
                        ? $telefone
                        : null,

                'senha' => $senha,
                'avatar' => null
            ]);

            respond(
                true,
                [
                    'message' =>
                        'Conta criada com sucesso.',

                    'usuario_id' =>
                        $id,

                    'redirect' =>
                        '/erpplus/index.php?page=login'
                ],
                201
            );

        /*
         * =====================================================
         * LOGOUT
         * =====================================================
         */
        case 'logout':
            if (
                strtoupper(
                    $_SERVER['REQUEST_METHOD']
                    ?? 'GET'
                ) !== 'POST'
            ) {
                respond(
                    false,
                    ['message' => 'Método inválido.'],
                    405
                );
            }

            Auth::logout();

            respond(
                true,
                [
                    'message' =>
                        'Sessão terminada com sucesso.',

                    'redirect' =>
                        '/erpplus/index.php?page=login'
                ]
            );

        /*
         * =====================================================
         * UTILIZADOR AUTENTICADO
         * =====================================================
         */
        case 'me':
            Auth::iniciarSessao();

            if (!Auth::check()) {
                respond(
                    false,
                    ['message' => 'Não autenticado.'],
                    401
                );
            }

            $usuarioId =
                (int)(Auth::id() ?? 0);

            $empresaId =
                (int)(Auth::empresaId() ?? 0);

            /*
             * O buscarPorId atual exige empresa_id.
             * Para utilizadores ligados a empresa, podemos usá-lo.
             */
            if ($empresaId > 0) {
                $usuario =
                    $usuarioModel->buscarPorId(
                        $usuarioId,
                        $empresaId
                    );
            } else {
                /*
                 * Para utilizadores sem empresa, como marketplace,
                 * usamos o método global adicionado abaixo.
                 */
                $usuario =
                    $usuarioModel->buscarPorIdGlobal(
                        $usuarioId
                    );
            }

            if (!$usuario) {
                respond(
                    false,
                    [
                        'message' =>
                            'Utilizador não encontrado.'
                    ],
                    404
                );
            }

            respond(
                true,
                [
                    'data' => [
                        'id' =>
                            (int)$usuario['id'],

                        'nome' =>
                            $usuario['nome']
                            ?? 'Utilizador',

                        'username' =>
                            $usuario['username']
                            ?? '',

                        'email' =>
                            $usuario['email']
                            ?? '',

                        'avatar' =>
                            $usuario['avatar']
                            ?? null,

                        'perfil_id' =>
                            (int)$usuario['perfil_id'],

                        'perfil_nome' =>
                            $usuario['perfil_nome']
                            ?? '',

                        'status_id' =>
                            (int)$usuario['status_id'],

                        'status_nome' =>
                            $usuario['status_nome']
                            ?? '',

                        'empresa_id' =>
                            !empty(
                                $usuario['empresa_id']
                            )
                                ? (int)$usuario['empresa_id']
                                : null,

                        'empresa_nome' =>
                            $usuario['empresa_nome']
                            ?? '',

                        'filial_id' =>
                            !empty(
                                $usuario['filial_id']
                            )
                                ? (int)$usuario['filial_id']
                                : null,

                        'filial_nome' =>
                            $usuario['filial_nome']
                            ?? ''
                    ]
                ]
            );

        default:
            respond(
                false,
                ['message' => 'Ação inválida.'],
                400
            );
    }
} catch (PDOException $e) {
    error_log(
        sprintf(
            '[auth_ajax][PDO] %s em %s:%d',
            $e->getMessage(),
            $e->getFile(),
            $e->getLine()
        )
    );

    respond(
        false,
        [
            'message' =>
                'Ocorreu um erro ao consultar a base de dados.',

            'erro' =>
                $e->getMessage()
        ],
        500
    );
} catch (Throwable $e) {
    error_log(
        sprintf(
            '[auth_ajax] %s em %s:%d',
            $e->getMessage(),
            $e->getFile(),
            $e->getLine()
        )
    );

    respond(
        false,
        [
            'message' =>
                $e->getMessage(),

            'arquivo' =>
                basename($e->getFile()),

            'linha' =>
                $e->getLine()
        ],
        500
    );
}