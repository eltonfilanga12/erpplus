<?php

declare(strict_types=1);

session_start();

/*
|--------------------------------------------------------------------------
| Inicialização do ERPPlus
|--------------------------------------------------------------------------
*/

define('APP_INIT', true);

/*
|--------------------------------------------------------------------------
| Core
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/app/core/Database.php';
require_once __DIR__ . '/app/core/Auth.php';

/*
|--------------------------------------------------------------------------
| Controllers
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/app/controllers/AuthController.php';
require_once __DIR__ . '/app/models/Menu.php';
require_once __DIR__ . '/app/models/Modulo.php';
require_once __DIR__ . '/app/models/Permissao.php';
require_once __DIR__ . '/app/models/Perfil.php';
require_once __DIR__ . '/app/controllers/PerfilController.php';
require_once __DIR__ . '/app/controllers/PermissaoController.php';
require_once __DIR__ . '/app/models/Usuario.php';
require_once __DIR__ . '/app/controllers/UsuarioController.php';
require_once __DIR__ . '/app/models/Empresa.php';
require_once __DIR__ . '/app/controllers/EmpresaController.php';
require_once __DIR__ . '/app/models/Filial.php';
require_once __DIR__ . '/app/controllers/FilialController.php';
require_once __DIR__ . '/app/models/Armazem.php';
require_once __DIR__ . '/app/controllers/ArmazemController.php';
require_once __DIR__ . '/app/models/Categoria.php';
require_once __DIR__ . '/app/controllers/CategoriaController.php';
require_once __DIR__ . '/app/models/Produto.php';
require_once __DIR__ . '/app/controllers/ProdutoController.php';
require_once __DIR__ . '/app/models/Fornecedor.php';
require_once __DIR__ . '/app/controllers/FornecedorController.php';
require_once __DIR__ . '/app/models/Compra.php';
require_once __DIR__ . '/app/controllers/CompraController.php';
require_once __DIR__ . '/app/services/EstoqueService.php';
require_once __DIR__ . '/app/models/Cliente.php';
require_once __DIR__ . '/app/controllers/ClienteController.php';
require_once __DIR__ . '/app/models/Venda.php';
require_once __DIR__ . '/app/controllers/VendaController.php';
require_once __DIR__ . '/app/models/ContaReceber.php';
require_once __DIR__ . '/app/controllers/ContaReceberController.php';
require_once __DIR__ . '/app/models/ContaPagar.php';
require_once __DIR__ . '/app/controllers/ContaPagarController.php';
require_once __DIR__ . '/app/models/FormaPagamento.php';
require_once __DIR__ . '/app/controllers/FormaPagamentoController.php';
require_once __DIR__ . '/app/models/Caixa.php';
require_once __DIR__ . '/app/controllers/CaixaController.php';
require_once __DIR__ . '/app/models/Banco.php';
require_once __DIR__ . '/app/controllers/BancoController.php';
require_once __DIR__ . '/app/models/MovimentoBancario.php';
require_once __DIR__ . '/app/controllers/MovimentoBancarioController.php';
require_once __DIR__ . '/app/models/FluxoCaixa.php';
require_once __DIR__ . '/app/controllers/FluxoCaixaController.php';
require_once __DIR__ . '/app/models/CentroCusto.php';
require_once __DIR__ . '/app/controllers/CentroCustoController.php';
require_once __DIR__ . '/app/models/IndicadorFinanceiro.php';
require_once __DIR__ . '/app/controllers/IndicadorFinanceiroController.php';
require_once __DIR__ . '/app/models/PlanoConta.php';
require_once __DIR__ . '/app/controllers/PlanoContaController.php';
require_once __DIR__ . '/app/models/ExercicioContabilistico.php';
require_once __DIR__ . '/app/controllers/ExercicioContabilisticoController.php';
require_once __DIR__ . '/app/models/DiarioContabilistico.php';
require_once __DIR__ . '/app/controllers/DiarioContabilisticoController.php';
require_once __DIR__ . '/app/models/LancamentoContabilistico.php';
require_once __DIR__ . '/app/controllers/LancamentoContabilisticoController.php';
require_once __DIR__ . '/app/models/Balancete.php';
require_once __DIR__ . '/app/controllers/BalanceteController.php';
require_once __DIR__ . '/app/models/RazaoContabilistico.php';
require_once __DIR__ . '/app/controllers/RazaoContabilisticoController.php';
require_once __DIR__ . '/app/models/DiarioGeral.php';
require_once __DIR__ . '/app/controllers/DiarioGeralController.php';
require_once __DIR__ . '/app/models/DemonstracaoResultados.php';
require_once __DIR__ . '/app/controllers/DemonstracaoResultadosController.php';
require_once __DIR__ . '/app/models/BalancoPatrimonial.php';
require_once __DIR__ . '/app/controllers/BalancoPatrimonialController.php';
require_once __DIR__ . '/app/models/FechoExercicio.php';
require_once __DIR__ . '/app/controllers/FechoExercicioController.php';
require_once __DIR__ . '/app/models/AberturaExercicio.php';
require_once __DIR__ . '/app/controllers/AberturaExercicioController.php';
require_once __DIR__ . '/app/models/DemonstracaoFluxoCaixa.php';
require_once __DIR__ . '/app/controllers/DemonstracaoFluxoCaixaController.php';
require_once __DIR__ . '/app/models/IndicadoresContabilisticos.php';
require_once __DIR__ . '/app/controllers/IndicadoresContabilisticosController.php';
require_once __DIR__ . '/app/models/Departamento.php';
require_once __DIR__ . '/app/controllers/DepartamentoController.php';
require_once __DIR__ . '/app/models/Cargo.php';
require_once __DIR__ . '/app/controllers/CargoController.php';
require_once __DIR__ . '/app/models/TipoContrato.php';
require_once __DIR__ . '/app/controllers/TipoContratoController.php';
require_once __DIR__ . '/app/models/HorarioTrabalho.php';
require_once __DIR__ . '/app/controllers/HorarioTrabalhoController.php';
require_once __DIR__ . '/app/models/CalendarioLaboral.php';
require_once __DIR__ . '/app/controllers/CalendarioLaboralController.php';
require_once __DIR__ . '/app/models/Funcionario.php';
require_once __DIR__ . '/app/controllers/FuncionarioController.php';
require_once __DIR__ . '/app/models/ContratoTrabalho.php';
require_once __DIR__ . '/app/controllers/ContratoTrabalhoController.php';
require_once __DIR__ . '/app/models/Assiduidade.php';
require_once __DIR__ . '/app/controllers/AssiduidadeController.php';
require_once __DIR__ . '/app/models/PeriodoSalarial.php';
require_once __DIR__ . '/app/controllers/PeriodoSalarialController.php';
require_once __DIR__ . '/app/models/RubricaSalarial.php';
require_once __DIR__ . '/app/controllers/RubricaSalarialController.php';
require_once __DIR__ . '/app/models/SalarioFuncionario.php';
require_once __DIR__ . '/app/controllers/SalarioFuncionarioController.php';
require_once __DIR__ . '/app/models/ProcessamentoSalarial.php';
require_once __DIR__ . '/app/controllers/ProcessamentoSalarialController.php';
require_once __DIR__ . '/app/models/FolhaSalarial.php';
require_once __DIR__ . '/app/controllers/FolhaSalarialController.php';
require_once __DIR__ . '/app/models/ReciboSalario.php';
require_once __DIR__ . '/app/controllers/ReciboSalarioController.php';
require_once __DIR__
    . '/app/models/PagamentoSalarial.php';

require_once __DIR__
    . '/app/controllers/PagamentoSalarialController.php';
require_once __DIR__ . '/app/models/ConfiguracaoContabilisticaRh.php';
require_once __DIR__ . '/app/controllers/ConfiguracaoContabilisticaRhController.php';
require_once __DIR__ . '/app/models/RubricaSalarialConta.php';
require_once __DIR__ . '/app/controllers/RubricaSalarialContaController.php';
require_once __DIR__ . '/app/models/Marca.php';
require_once __DIR__ . '/app/controllers/MarcaController.php';
require_once __DIR__ . '/app/models/Unidade.php';
require_once __DIR__ . '/app/controllers/UnidadeController.php';
require_once __DIR__ . '/app/models/Stock.php';
require_once __DIR__ . '/app/controllers/StockController.php';

require_once __DIR__ . '/app/models/MovimentoStock.php';
require_once __DIR__ . '/app/controllers/MovimentoStockController.php';
require_once __DIR__ . '/app/models/EntradaStock.php';
require_once __DIR__ . '/app/controllers/EntradaStockController.php';
require_once __DIR__ . '/app/models/SaidaStock.php';
require_once __DIR__ . '/app/controllers/SaidaStockController.php';
require_once __DIR__ . '/app/models/TransferenciaStock.php';
require_once __DIR__ . '/app/controllers/TransferenciaStockController.php';
require_once __DIR__ . '/app/models/AjusteStock.php';
require_once __DIR__ . '/app/controllers/AjusteStockController.php';
require_once __DIR__ . '/app/models/InventarioStock.php';
require_once __DIR__ . '/app/controllers/InventarioStockController.php';
require_once __DIR__ . '/app/models/RelatorioOperacional.php';
require_once __DIR__ . '/app/controllers/RelatorioOperacionalController.php';
require_once __DIR__ . '/app/models/RelatorioFinanceiro.php';
require_once __DIR__ . '/app/controllers/RelatorioFinanceiroController.php';
require_once __DIR__ . '/app/models/RelatorioContabilistico.php';
require_once __DIR__ . '/app/controllers/RelatorioContabilisticoController.php';
require_once __DIR__ . '/app/models/RelatorioRH.php';
require_once __DIR__ . '/app/controllers/RelatorioRHController.php';
require_once __DIR__ . '/app/models/Dashboard.php';
require_once __DIR__ . '/app/controllers/DashboardController.php';
require_once __DIR__ . '/app/models/MovimentoCaixa.php';
require_once __DIR__ . '/app/controllers/MovimentoCaixaController.php';
require_once __DIR__ . '/app/models/PerfilUsuario.php';
require_once __DIR__ . '/app/controllers/PerfilUsuarioController.php';
require_once __DIR__ . '/app/models/Irps.php';
require_once __DIR__ . '/app/controllers/IrpsController.php';
require_once __DIR__ . '/app/models/AtivoEmpresa.php';
require_once __DIR__ . '/app/controllers/AtivoEmpresaController.php';
require_once __DIR__ . '/app/models/AnaliseGastos.php';
require_once __DIR__ . '/app/controllers/AnaliseGastosController.php';
require_once __DIR__ . '/app/models/SaldosAbertura.php';
require_once __DIR__ . '/app/controllers/SaldosAberturaController.php';
require_once __DIR__ . '/app/models/Despesa.php';
require_once __DIR__ . '/app/controllers/DespesaController.php';
require_once __DIR__ . '/app/models/Log.php';
require_once __DIR__ . '/app/controllers/LogController.php';
require_once __DIR__ . '/app/models/Auditoria.php';
require_once __DIR__ . '/app/controllers/AuditoriaController.php';
require_once __DIR__ . '/app/models/PagamentoIva.php';
require_once __DIR__ . '/app/controllers/PagamentoIvaController.php';
require_once __DIR__ . '/app/models/ObrigacaoFiscal.php';
require_once __DIR__ . '/app/controllers/ObrigacaoFiscalController.php';
require_once __DIR__ . '/app/models/Cotacao.php';
require_once __DIR__ . '/app/controllers/CotacaoController.php';
require_once __DIR__ . '/app/models/ApuramentoISPC.php';
require_once __DIR__ . '/app/controllers/ApuramentoISPCController.php';
require_once __DIR__ . '/app/models/ConfiguracaoFiscal.php';
require_once __DIR__ . '/app/controllers/ConfiguracaoFiscalController.php';
require_once __DIR__ . '/app/models/EscalaoISPC.php';
require_once __DIR__ . '/app/controllers/EscalaoISPCController.php';






/*
|--------------------------------------------------------------------------
| Página solicitada
|--------------------------------------------------------------------------
*/

$page = $_GET['page'] ?? 'dashboard';

/*
|--------------------------------------------------------------------------
| Páginas públicas
|--------------------------------------------------------------------------
*/

$publicPages = [
    'login'
];

/*
|--------------------------------------------------------------------------
| Login
|--------------------------------------------------------------------------
*/

if ($page === 'login') {

    // Se já estiver autenticado vai para o dashboard
    if (Auth::check()) {
        header('Location: /erpplus/index.php?page=dashboard');
        exit;
    }

    require __DIR__ . '/app/views/auth/login.php';
    exit;
}

/*
|--------------------------------------------------------------------------
| Demais páginas exigem login
|--------------------------------------------------------------------------
*/

Auth::requireLogin('/erpplus/index.php?page=login');


/*
|--------------------------------------------------------------------------
| Rotas permitidas
|--------------------------------------------------------------------------
*/

$allowedPages = [

    // Dashboard
    'dashboard',

    // Administração
    'empresas',
    'filiais',
    'usuarios',
    'perfis',
    'modulos',
    'permissoes',
    'perfil_usuario',
    'logs',
    'auditoria',
    'pagamento_iva',
    'obrigacoes_fiscais',

    // Cadastros
    'clientes',
    'fornecedores',
    'categorias',
    'produtos',
    'armazens',
    'marcas',
    'unidades',
    'irps',
    'ativos_empresa',
    'analise_gastos',
    
    'stock',
    'movimentos_stock',
    'entrada_stock',
    'saida_stock',
    'transferencias_stock',
    'ajustes_stock',
    'inventario_stock',
    'configuracao_fiscal',
    'apuramentos_ispc',
    'escaloes_ispc',

    // Operações
    'compras',
    'vendas',
    'cotacoes',
    'financeiro',
    'contas_receber',
    'contas_pagar',
    'caixa',
    'bancos',
    'movimentos_bancarios',
    'movimentos_caixa',
    'fluxo_caixa',
    'centros_custo',
    'conciliacao_bancaria',
    'saldos_abertura',
    'despesas',

    // Sistema
    'relatorios',
    'configuracoes',
    'perfil',
    'logs',
    'auditoria',
    'indicadores_financeiros',
    'contabilidade',
    'plano_contas',
    'exercicios_contabilisticos',
    'diarios_contabilisticos',
    'lancamentos_contabilisticos',
    'diario_geral',
    'razao_geral',
    'balancete',
    'demonstracao_resultados',
    'balanco_patrimonial',
    'demonstracao_fluxo_caixa',
    'indicadores_contabilisticos',
    'fecho_exercicio',
    'abertura_exercicio',
    'configuracao_contabilistica',

    'departamentos',
    'cargos',
    'tipos_contratos',
    'horarios_trabalho',
    'calendario_laboral',
    'funcionarios',
    'contratos_trabalho',
    'assiduidade',
    'periodos_salariais',
    'rubricas_salariais',
    'salarios_funcionarios',
    'processamentos_salariais',
    'folhas_salariais',
    'recibos_salarios',
    'imprimir_recibo_salario',
    'pagamentos_salariais',
    'configuracao_contabilistica_rh',
    'rubricas_salariais_contas',

    'relatorios_operacionais',
    'relatorios_financeiros',
    'relatorios_contabilisticos',
    'relatorios_rh'
];

/*
|--------------------------------------------------------------------------
| Página inválida
|--------------------------------------------------------------------------
*/

if (!in_array($page, $allowedPages, true)) {
    $page = 'dashboard';
}

/*
|--------------------------------------------------------------------------
| View
|--------------------------------------------------------------------------
*/

$view = __DIR__ . "/app/views/{$page}.php";

if (!file_exists($view)) {
    $view = __DIR__ . "/app/views/dashboard.php";
}

/*
|--------------------------------------------------------------------------
| Páginas sem layout principal
|--------------------------------------------------------------------------
*/

$paginasSemLayout = [
    'imprimir_recibo_salario'
];

if (
    in_array(
        $page,
        $paginasSemLayout,
        true
    )
) {
    require $view;
    exit;
}

/*
|--------------------------------------------------------------------------
| Layout
|--------------------------------------------------------------------------
*/

require __DIR__ . '/app/views/partials/head.php';
require __DIR__ . '/app/views/partials/header.php';
require __DIR__ . '/app/views/partials/sidebar.php';

require $view;

require __DIR__ . '/app/views/partials/footer.php';
