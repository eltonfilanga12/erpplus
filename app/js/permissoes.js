'use strict';

(() => {
    const PERMISSAO_URL =
        '/erpplus/app/ajax/permissao_ajax.php';

    let moduloPermissoesInicializado =
        false;

    if (
        document.readyState === 'loading'
    ) {
        document.addEventListener(
            'DOMContentLoaded',
            iniciarPermissoes
        );
    } else {
        iniciarPermissoes();
    }

    async function iniciarPermissoes() {
        if (
            moduloPermissoesInicializado
        ) {
            return;
        }

        const tabela =
            document.getElementById(
                'permissaoTabela'
            );

        if (!tabela) {
            return;
        }

        moduloPermissoesInicializado =
            true;

        configurarEventos();

        await carregarPerfis();
    }

    function configurarEventos() {
        document
            .getElementById(
                'permissaoPerfilId'
            )
            ?.addEventListener(
                'change',
                carregarPermissoesPerfil
            );

        document
            .getElementById(
                'permissaoBtnGuardar'
            )
            ?.addEventListener(
                'click',
                guardarPermissoes
            );

        document
            .getElementById(
                'permissaoMarcarTodas'
            )
            ?.addEventListener(
                'change',
                marcarTodas
            );
    }

    async function requisicao(
        url,
        opcoes = {}
    ) {
        const resposta =
            await fetch(
                url,
                {
                    credentials:
                        'same-origin',

                    cache:
                        'no-store',

                    ...opcoes
                }
            );

        const texto =
            await resposta.text();

        let resultado;

        try {
            resultado =
                JSON.parse(texto);
        } catch (erro) {
            console.error(
                'Resposta inválida:',
                texto
            );

            throw new Error(
                'O servidor devolveu uma resposta inválida.'
            );
        }

        const sucesso =
            resultado.sucesso
            ?? resultado.success
            ?? false;

        if (
            !resposta.ok
            || sucesso !== true
        ) {
            throw new Error(
                resultado.mensagem
                || resultado.message
                || 'Não foi possível concluir a operação.'
            );
        }

        return {
            ...resultado,

            dados:
                resultado.dados
                ?? resultado.data
                ?? []
        };
    }

    async function carregarPerfis() {
        const select =
            document.getElementById(
                'permissaoPerfilId'
            );

        if (!select) {
            return;
        }

        try {
            const resultado =
                await requisicao(
                    `${PERMISSAO_URL}?acao=perfis`
                );

            const perfis =
                Array.isArray(
                    resultado.dados
                )
                    ? resultado.dados
                    : [];

            select.replaceChildren();

            select.add(
                new Option(
                    perfis.length
                        ? 'Selecione um perfil...'
                        : 'Nenhum perfil disponível',
                    ''
                )
            );

            perfis.forEach(perfil => {
                select.add(
                    new Option(
                        perfil.nome,
                        String(perfil.id)
                    )
                );
            });
        } catch (erro) {
            select.replaceChildren(
                new Option(
                    'Erro ao carregar perfis',
                    ''
                )
            );

            mostrarMensagem(
                erro.message,
                'danger'
            );
        }
    }

    async function carregarPermissoesPerfil(
        evento
    ) {
        const perfilId =
            evento.currentTarget.value;

        const tbody =
            document.getElementById(
                'permissaoTabelaBody'
            );

        const botao =
            document.getElementById(
                'permissaoBtnGuardar'
            );

        if (!perfilId) {
            botao.disabled = true;

            tbody.innerHTML = `
                <tr>
                    <td
                        colspan="6"
                        class="text-center text-muted"
                    >
                        Selecione um perfil.
                    </td>
                </tr>
            `;

            return;
        }

        tbody.innerHTML = `
            <tr>
                <td
                    colspan="6"
                    class="text-center"
                >
                    <i class="fa fa-spinner fa-spin"></i>
                    A carregar permissões...
                </td>
            </tr>
        `;

        try {
            const resultado =
                await requisicao(
                    `${PERMISSAO_URL}?acao=carregar&perfil_id=${encodeURIComponent(perfilId)}`
                );

            renderizarPermissoes(
                resultado.dados
            );

            botao.disabled = false;
        } catch (erro) {
            botao.disabled = true;

            tbody.innerHTML = `
                <tr>
                    <td
                        colspan="6"
                        class="text-center text-danger"
                    >
                        ${escaparHtml(
                erro.message
            )}
                    </td>
                </tr>
            `;
        }
    }

    function renderizarPermissoes(
        dados
    ) {
        const tbody =
            document.getElementById(
                'permissaoTabelaBody'
            );

        const modulos =
            Array.isArray(dados)
                ? dados
                : [];

        if (!modulos.length) {
            tbody.innerHTML = `
                <tr>
                    <td
                        colspan="6"
                        class="text-center text-muted"
                    >
                        Nenhum módulo encontrado.
                    </td>
                </tr>
            `;

            return;
        }

        tbody.innerHTML =
            modulos.map(modulo => `
                <tr
                    data-modulo-id="${modulo.modulo_id}"
                >
                    <td>
                        <strong>
                            ${escaparHtml(
                modulo.modulo_nome
            )}
                        </strong>
                    </td>

                    ${criarCheckbox(
                'visualizar',
                modulo.visualizar
            )}

                    ${criarCheckbox(
                'criar',
                modulo.criar
            )}

                    ${criarCheckbox(
                'editar',
                modulo.editar
            )}

                    ${criarCheckbox(
                'eliminar',
                modulo.eliminar
            )}

                    ${criarCheckbox(
                'exportar',
                modulo.exportar
            )}
                </tr>
            `).join('');
    }

    function criarCheckbox(
        classe,
        marcado
    ) {
        return `
            <td class="text-center">
                <input
                    type="checkbox"
                    class="permissao-checkbox ${classe}"
                    ${Number(marcado) === 1
                ? 'checked'
                : ''}
                >
            </td>
        `;
    }

    function marcarTodas(evento) {
        document
            .querySelectorAll(
                '.permissao-checkbox'
            )
            .forEach(checkbox => {
                checkbox.checked =
                    evento.currentTarget.checked;
            });
    }

    async function guardarPermissoes() {
        const perfilId =
            document.getElementById(
                'permissaoPerfilId'
            )?.value;

        const botao =
            document.getElementById(
                'permissaoBtnGuardar'
            );

        if (!perfilId) {
            mostrarMensagem(
                'Selecione um perfil.',
                'danger'
            );

            return;
        }

        const permissoes =
            Array.from(
                document.querySelectorAll(
                    '#permissaoTabelaBody tr[data-modulo-id]'
                )
            ).map(linha => ({
                modulo_id:
                    Number(
                        linha.dataset.moduloId
                    ),

                visualizar:
                    linha.querySelector(
                        '.visualizar'
                    )?.checked ? 1 : 0,

                criar:
                    linha.querySelector(
                        '.criar'
                    )?.checked ? 1 : 0,

                editar:
                    linha.querySelector(
                        '.editar'
                    )?.checked ? 1 : 0,

                eliminar:
                    linha.querySelector(
                        '.eliminar'
                    )?.checked ? 1 : 0,

                exportar:
                    linha.querySelector(
                        '.exportar'
                    )?.checked ? 1 : 0
            }));

        const htmlOriginal =
            botao.innerHTML;

        try {
            botao.disabled = true;

            botao.innerHTML = `
                <i class="fa fa-spinner fa-spin"></i>
                A guardar...
            `;

            const resultado =
                await requisicao(
                    `${PERMISSAO_URL}?acao=guardar`,
                    {
                        method: 'POST',

                        headers: {
                            'Content-Type':
                                'application/json'
                        },

                        body:
                            JSON.stringify({
                                perfil_id:
                                    Number(perfilId),

                                permissoes
                            })
                    }
                );

            mostrarMensagem(
                resultado.mensagem,
                'success'
            );
        } catch (erro) {
            mostrarMensagem(
                erro.message,
                'danger'
            );
        } finally {
            botao.disabled = false;
            botao.innerHTML =
                htmlOriginal;
        }
    }

    function mostrarMensagem(
        mensagem,
        tipo
    ) {
        const elemento =
            document.getElementById(
                'permissaoMensagem'
            );

        if (!elemento) {
            return;
        }

        elemento.className =
            `alert alert-${tipo}`;

        elemento.textContent =
            mensagem;
    }

    function escaparHtml(valor) {
        const elemento =
            document.createElement(
                'div'
            );

        elemento.textContent =
            valor ?? '';

        return elemento.innerHTML;
    }
})();