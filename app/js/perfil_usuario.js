'use strict';

/*
|--------------------------------------------------------------------------
| Configuração
|--------------------------------------------------------------------------
*/

const PERFIL_USUARIO_URL =
    'app/ajax/perfil_usuario_ajax.php';

const AVATAR_PADRAO =
    '/erpplus/public/assets/img/user.jpg';


/*
|--------------------------------------------------------------------------
| Inicialização
|--------------------------------------------------------------------------
*/

document.addEventListener(
    'DOMContentLoaded',
    function () {

        carregarPerfilUsuario();

        configurarEventosPerfil();
    }
);


/*
|--------------------------------------------------------------------------
| Eventos
|--------------------------------------------------------------------------
*/

function configurarEventosPerfil() {
    const formPerfil =
        document.getElementById(
            'formEditarPerfil'
        );

    if (formPerfil) {

        formPerfil.addEventListener(
            'submit',
            salvarPerfilUsuario
        );
    }


    const formSenha =
        document.getElementById(
            'formAlterarSenha'
        );

    if (formSenha) {

        formSenha.addEventListener(
            'submit',
            alterarSenhaUsuario
        );
    }


    const inputAvatar =
        document.getElementById(
            'inputAvatarPerfil'
        );

    if (inputAvatar) {

        inputAvatar.addEventListener(
            'change',
            alterarAvatarUsuario
        );
    }


    const botaoRemoverAvatar =
        document.getElementById(
            'btnRemoverAvatar'
        );

    if (botaoRemoverAvatar) {

        botaoRemoverAvatar.addEventListener(
            'click',
            removerAvatarUsuario
        );
    }
}


/*
|--------------------------------------------------------------------------
| Requisição
|--------------------------------------------------------------------------
*/

async function requisicaoPerfil(
    url,
    opcoes = {}
) {
    const resposta =
        await fetch(
            url,
            {
                credentials:
                    'same-origin',
                ...opcoes
            }
        );

    let dados = null;

    try {

        dados =
            await resposta.json();

    } catch (erro) {

        throw new Error(
            'O servidor retornou uma resposta inválida.'
        );
    }

    if (
        !resposta.ok
        || !dados.sucesso
    ) {
        throw new Error(
            dados.mensagem
            || 'Não foi possível processar a operação.'
        );
    }

    return dados;
}


/*
|--------------------------------------------------------------------------
| Carregar perfil
|--------------------------------------------------------------------------
*/

async function carregarPerfilUsuario() {
    mostrarLoadingPerfil(
        true
    );

    limparMensagemPerfil();

    try {

        const resultado =
            await requisicaoPerfil(
                `${PERFIL_USUARIO_URL}?acao=carregar`
            );

        const dados =
            resultado.dados || {};

        preencherCabecalhoPerfil(
            dados.cabecalho || {}
        );

        preencherDetalhesPerfil(
            dados.perfil || {}
        );

        preencherFormularioPerfil(
            dados.perfil || {}
        );

    } catch (erro) {

        console.error(
            '[PerfilUsuario]',
            erro
        );

        mostrarErroPerfil(
            erro.message
        );

    } finally {

        mostrarLoadingPerfil(
            false
        );
    }
}


/*
|--------------------------------------------------------------------------
| Cabeçalho
|--------------------------------------------------------------------------
*/

function preencherCabecalhoPerfil(
    dados
) {
    definirTexto(
        'perfilNome',
        dados.nome || '-'
    );

    definirTexto(
        'perfilFuncao',
        dados.perfil || '-'
    );

    definirTexto(
        'perfilEmpresa',
        dados.empresa || '-'
    );

    definirTexto(
        'perfilFilial',
        dados.filial || 'Sem filial'
    );

    definirTexto(
        'perfilLocalizacao',
        dados.localizacao || '-'
    );

    definirTexto(
        'perfilUltimoLogin',
        formatarDataHora(
            dados.ultimo_login
        )
    );

    atualizarAvatarNaTela(
        dados.avatar
    );

    atualizarStatusPerfil(
        dados
    );
}


/*
|--------------------------------------------------------------------------
| Status
|--------------------------------------------------------------------------
*/

function atualizarStatusPerfil(
    dados
) {
    const elemento =
        document.getElementById(
            'perfilStatus'
        );

    if (!elemento) {
        return;
    }

    elemento.textContent =
        dados.status || '-';

    elemento.className =
        'btn btn-sm';

    const codigo =
        String(
            dados.status || ''
        )
            .trim()
            .toUpperCase();

    if (
        codigo === 'ATIVO'
        || codigo === 'ACTIVO'
    ) {
        elemento.classList.add(
            'btn-success'
        );
    } else if (
        codigo === 'INATIVO'
        || codigo === 'INACTIVO'
    ) {
        elemento.classList.add(
            'btn-secondary'
        );
    } else if (
        codigo === 'BLOQUEADO'
    ) {
        elemento.classList.add(
            'btn-danger'
        );
    } else if (
        codigo === 'PENDENTE'
    ) {
        elemento.classList.add(
            'btn-warning'
        );
    } else {
        elemento.classList.add(
            'btn-info'
        );
    }
}


/*
|--------------------------------------------------------------------------
| Detalhes
|--------------------------------------------------------------------------
*/

function preencherDetalhesPerfil(
    perfil
) {
    definirTexto(
        'perfilDetalheNome',
        perfil.nome || '-'
    );

    definirTexto(
        'perfilDetalheUsername',
        perfil.username || '-'
    );

    definirTexto(
        'perfilDetalheEmail',
        perfil.email || '-'
    );

    definirTexto(
        'perfilDetalheTelefone',
        perfil.telefone || '-'
    );

    definirTexto(
        'perfilDetalhePerfil',
        perfil.perfil_descricao
        || perfil.perfil_nome
        || '-'
    );

    definirTexto(
        'perfilDetalheEmpresa',
        perfil.empresa_nome || '-'
    );

    definirTexto(
        'perfilDetalheFilial',
        perfil.filial_nome
        || 'Sem filial'
    );

    definirTexto(
        'perfilDetalheStatus',
        perfil.status_nome || '-'
    );

    definirTexto(
        'perfilDetalheCriadoEm',
        formatarDataHora(
            perfil.criado_em
        )
    );

    definirTexto(
        'perfilDetalheUltimoLogin',
        formatarDataHora(
            perfil.ultimo_login_em
        )
    );
}


/*
|--------------------------------------------------------------------------
| Formulário de edição
|--------------------------------------------------------------------------
*/

function preencherFormularioPerfil(
    perfil
) {
    definirValor(
        'editarPerfilNome',
        perfil.nome
    );

    definirValor(
        'editarPerfilUsername',
        perfil.username
    );

    definirValor(
        'editarPerfilEmail',
        perfil.email
    );

    definirValor(
        'editarPerfilTelefone',
        perfil.telefone
    );
}


/*
|--------------------------------------------------------------------------
| Salvar perfil
|--------------------------------------------------------------------------
*/

async function salvarPerfilUsuario(
    evento
) {
    evento.preventDefault();

    limparMensagemPerfil();

    const formulario =
        evento.currentTarget;

    const botao =
        formulario.querySelector(
            'button[type="submit"]'
        );

    bloquearBotao(
        botao,
        true,
        'A guardar...'
    );

    try {

        const formData =
            new FormData(
                formulario
            );

        formData.set(
            'acao',
            'atualizar'
        );

        const resultado =
            await requisicaoPerfil(
                PERFIL_USUARIO_URL,
                {
                    method: 'POST',
                    body: formData
                }
            );

        mostrarSucessoPerfil(
            resultado.mensagem
            || 'Perfil atualizado com sucesso.'
        );

        fecharModalPerfil(
            'edit_personal_details'
        );

        await carregarPerfilUsuario();

    } catch (erro) {

        mostrarErroPerfil(
            erro.message
        );

    } finally {

        bloquearBotao(
            botao,
            false
        );
    }
}


/*
|--------------------------------------------------------------------------
| Alterar senha
|--------------------------------------------------------------------------
*/

async function alterarSenhaUsuario(
    evento
) {
    evento.preventDefault();

    limparMensagemSenha();

    const formulario =
        evento.currentTarget;

    const senhaAtual =
        formulario.querySelector(
            '[name="senha_atual"]'
        )?.value || '';

    const novaSenha =
        formulario.querySelector(
            '[name="nova_senha"]'
        )?.value || '';

    const confirmarSenha =
        formulario.querySelector(
            '[name="confirmar_senha"]'
        )?.value || '';

    if (!senhaAtual) {

        mostrarErroSenha(
            'Informe a senha atual.'
        );

        return;
    }

    if (
        novaSenha.length < 8
    ) {

        mostrarErroSenha(
            'A nova senha deve possuir pelo menos 8 caracteres.'
        );

        return;
    }

    if (
        novaSenha !==
        confirmarSenha
    ) {

        mostrarErroSenha(
            'A confirmação da nova senha não corresponde.'
        );

        return;
    }

    const botao =
        formulario.querySelector(
            'button[type="submit"]'
        );

    bloquearBotao(
        botao,
        true,
        'A alterar...'
    );

    try {

        const formData =
            new FormData(
                formulario
            );

        formData.set(
            'acao',
            'alterar_senha'
        );

        const resultado =
            await requisicaoPerfil(
                PERFIL_USUARIO_URL,
                {
                    method: 'POST',
                    body: formData
                }
            );

        formulario.reset();

        mostrarSucessoSenha(
            resultado.mensagem
            || 'Senha alterada com sucesso.'
        );

    } catch (erro) {

        mostrarErroSenha(
            erro.message
        );

    } finally {

        bloquearBotao(
            botao,
            false
        );
    }
}


/*
|--------------------------------------------------------------------------
| Alterar avatar
|--------------------------------------------------------------------------
*/

async function alterarAvatarUsuario(
    evento
) {
    const input =
        evento.currentTarget;

    const arquivo =
        input.files?.[0];

    if (!arquivo) {
        return;
    }

    const permitidos = [
        'image/jpeg',
        'image/png',
        'image/webp'
    ];

    if (
        !permitidos.includes(
            arquivo.type
        )
    ) {

        mostrarErroPerfil(
            'Selecione uma fotografia JPG, PNG ou WEBP.'
        );

        input.value = '';

        return;
    }

    if (
        arquivo.size >
        5 * 1024 * 1024
    ) {

        mostrarErroPerfil(
            'A fotografia deve possuir no máximo 5 MB.'
        );

        input.value = '';

        return;
    }

    /*
     * Pré-visualização imediata.
     */
    const urlTemporaria =
        URL.createObjectURL(
            arquivo
        );

    atualizarAvatarNaTela(
        urlTemporaria,
        true
    );

    const formData =
        new FormData();

    formData.append(
        'acao',
        'alterar_avatar'
    );

    formData.append(
        'avatar',
        arquivo
    );

    try {

        const resultado =
            await requisicaoPerfil(
                PERFIL_USUARIO_URL,
                {
                    method: 'POST',
                    body: formData
                }
            );

        const avatar =
            resultado.dados?.avatar;

        if (avatar) {

            atualizarAvatarNaTela(
                avatar
            );
        }

        atualizarAvatarCabecalhoSistema(
            avatar
        );

        mostrarSucessoPerfil(
            resultado.mensagem
            || 'Fotografia atualizada com sucesso.'
        );

    } catch (erro) {

        mostrarErroPerfil(
            erro.message
        );

        await carregarPerfilUsuario();

    } finally {

        URL.revokeObjectURL(
            urlTemporaria
        );

        input.value = '';
    }
}


/*
|--------------------------------------------------------------------------
| Remover avatar
|--------------------------------------------------------------------------
*/

async function removerAvatarUsuario(
    evento
) {
    evento.preventDefault();

    if (
        !window.confirm(
            'Deseja remover a fotografia do perfil?'
        )
    ) {
        return;
    }

    const botao =
        evento.currentTarget;

    bloquearBotao(
        botao,
        true,
        'A remover...'
    );

    try {

        const formData =
            new FormData();

        formData.append(
            'acao',
            'remover_avatar'
        );

        const resultado =
            await requisicaoPerfil(
                PERFIL_USUARIO_URL,
                {
                    method: 'POST',
                    body: formData
                }
            );

        atualizarAvatarNaTela(
            null
        );

        atualizarAvatarCabecalhoSistema(
            null
        );

        mostrarSucessoPerfil(
            resultado.mensagem
            || 'Fotografia removida com sucesso.'
        );

    } catch (erro) {

        mostrarErroPerfil(
            erro.message
        );

    } finally {

        bloquearBotao(
            botao,
            false
        );
    }
}


/*
|--------------------------------------------------------------------------
| Avatar
|--------------------------------------------------------------------------
*/

function atualizarAvatarNaTela(
    caminho,
    caminhoTemporario = false
) {
    const avatar =
        document.getElementById(
            'perfilAvatar'
        );

    if (!avatar) {
        return;
    }

    if (!caminho) {

        avatar.src =
            AVATAR_PADRAO;

        return;
    }

    avatar.src =
        caminhoTemporario
            ? caminho
            : normalizarCaminhoAvatar(
                caminho
            );
}


function atualizarAvatarCabecalhoSistema(
    caminho
) {
    const seletores = [
        '#avatarUsuarioHeader',
        '.user-img img',
        '.header-profile-user'
    ];

    seletores.forEach(
        seletor => {

            const elementos =
                document.querySelectorAll(
                    seletor
                );

            elementos.forEach(
                elemento => {

                    elemento.src =
                        caminho
                            ? normalizarCaminhoAvatar(
                                caminho
                            )
                            : AVATAR_PADRAO;
                }
            );
        }
    );
}


function normalizarCaminhoAvatar(
    caminho
) {
    if (!caminho) {
        return AVATAR_PADRAO;
    }

    const texto =
        String(caminho);

    if (
        texto.startsWith(
            'http://'
        )
        ||
        texto.startsWith(
            'https://'
        )
        ||
        texto.startsWith(
            'blob:'
        )
        ||
        texto.startsWith(
            'data:'
        )
        ||
        texto.startsWith(
            '/'
        )
    ) {
        return texto;
    }

    return texto;
}


/*
|--------------------------------------------------------------------------
| Mensagens gerais
|--------------------------------------------------------------------------
*/

function mostrarErroPerfil(
    mensagem
) {
    const elemento =
        document.getElementById(
            'perfilErro'
        );

    if (!elemento) {
        return;
    }

    elemento.textContent =
        mensagem;

    elemento.classList.remove(
        'd-none'
    );

    const sucesso =
        document.getElementById(
            'perfilSucesso'
        );

    if (sucesso) {
        sucesso.classList.add(
            'd-none'
        );
    }
}


function mostrarSucessoPerfil(
    mensagem
) {
    const elemento =
        document.getElementById(
            'perfilSucesso'
        );

    if (!elemento) {
        return;
    }

    elemento.textContent =
        mensagem;

    elemento.classList.remove(
        'd-none'
    );

    const erro =
        document.getElementById(
            'perfilErro'
        );

    if (erro) {
        erro.classList.add(
            'd-none'
        );
    }
}


function limparMensagemPerfil() {
    [
        'perfilErro',
        'perfilSucesso'
    ].forEach(
        id => {

            const elemento =
                document.getElementById(
                    id
                );

            if (elemento) {

                elemento.textContent = '';

                elemento.classList.add(
                    'd-none'
                );
            }
        }
    );
}


/*
|--------------------------------------------------------------------------
| Mensagens de senha
|--------------------------------------------------------------------------
*/

function mostrarErroSenha(
    mensagem
) {
    const erro =
        document.getElementById(
            'senhaErro'
        );

    const sucesso =
        document.getElementById(
            'senhaSucesso'
        );

    if (erro) {

        erro.textContent =
            mensagem;

        erro.classList.remove(
            'd-none'
        );
    }

    if (sucesso) {

        sucesso.classList.add(
            'd-none'
        );
    }
}


function mostrarSucessoSenha(
    mensagem
) {
    const sucesso =
        document.getElementById(
            'senhaSucesso'
        );

    const erro =
        document.getElementById(
            'senhaErro'
        );

    if (sucesso) {

        sucesso.textContent =
            mensagem;

        sucesso.classList.remove(
            'd-none'
        );
    }

    if (erro) {

        erro.classList.add(
            'd-none'
        );
    }
}


function limparMensagemSenha() {
    [
        'senhaErro',
        'senhaSucesso'
    ].forEach(
        id => {

            const elemento =
                document.getElementById(
                    id
                );

            if (elemento) {

                elemento.textContent = '';

                elemento.classList.add(
                    'd-none'
                );
            }
        }
    );
}


/*
|--------------------------------------------------------------------------
| Loading
|--------------------------------------------------------------------------
*/

function mostrarLoadingPerfil(
    mostrar
) {
    const elemento =
        document.getElementById(
            'perfilLoading'
        );

    if (!elemento) {
        return;
    }

    elemento.classList.toggle(
        'd-none',
        !mostrar
    );
}


/*
|--------------------------------------------------------------------------
| Botões
|--------------------------------------------------------------------------
*/

function bloquearBotao(
    botao,
    bloquear,
    texto = null
) {
    if (!botao) {
        return;
    }

    if (bloquear) {

        if (
            !botao.dataset
                .textoOriginal
        ) {
            botao.dataset
                .textoOriginal =
                botao.innerHTML;
        }

        botao.disabled = true;

        if (texto) {

            botao.innerHTML = `
                <span
                    class="spinner-border
                           spinner-border-sm
                           mr-1">
                </span>

                ${escaparHtml(texto)}
            `;
        }

    } else {

        botao.disabled = false;

        if (
            botao.dataset
                .textoOriginal
        ) {

            botao.innerHTML =
                botao.dataset
                    .textoOriginal;
        }
    }
}


/*
|--------------------------------------------------------------------------
| Modal
|--------------------------------------------------------------------------
*/

function fecharModalPerfil(
    id
) {
    const modal =
        document.getElementById(
            id
        );

    if (!modal) {
        return;
    }

    if (
        typeof $ !== 'undefined'
        && typeof $(modal).modal
        === 'function'
    ) {

        $(modal).modal(
            'hide'
        );

        return;
    }

    modal.classList.remove(
        'show'
    );

    modal.style.display =
        'none';
}


/*
|--------------------------------------------------------------------------
| Utilitários
|--------------------------------------------------------------------------
*/

function definirTexto(
    id,
    valor
) {
    const elemento =
        document.getElementById(
            id
        );

    if (elemento) {

        elemento.textContent =
            valor ?? '-';
    }
}


function definirValor(
    id,
    valor
) {
    const elemento =
        document.getElementById(
            id
        );

    if (elemento) {

        elemento.value =
            valor ?? '';
    }
}


function formatarDataHora(
    valor
) {
    if (!valor) {
        return '-';
    }

    let normalizado =
        String(valor).trim();

    /*
     * MariaDB normalmente devolve:
     * 2026-08-17 22:30:00
     */
    if (
        normalizado.includes(
            ' '
        )
        &&
        !normalizado.includes(
            'T'
        )
    ) {

        normalizado =
            normalizado.replace(
                ' ',
                'T'
            );
    }

    const data =
        new Date(
            normalizado
        );

    if (
        Number.isNaN(
            data.getTime()
        )
    ) {
        return valor;
    }

    return new Intl.DateTimeFormat(
        'pt-MZ',
        {
            day:
                '2-digit',

            month:
                '2-digit',

            year:
                'numeric',

            hour:
                '2-digit',

            minute:
                '2-digit'
        }
    ).format(
        data
    );
}


function escaparHtml(
    texto
) {
    if (
        texto === null
        || texto === undefined
    ) {
        return '';
    }

    return String(texto)
        .replace(
            /&/g,
            '&amp;'
        )
        .replace(
            /</g,
            '&lt;'
        )
        .replace(
            />/g,
            '&gt;'
        )
        .replace(
            /"/g,
            '&quot;'
        )
        .replace(
            /'/g,
            '&#039;'
        );
}