<?php

Auth::iniciarSessao();

$nome = $_SESSION['usuario_nome'] ?? 'Administrador';

$avatar = $_SESSION['usuario_avatar'] ?? '/erpplus/public/assets/img/user.jpg';

?>

<!-- Header -->
<div class="header">

	<!-- Logo -->
	<div class="header-left">

		<a href="/erpplus/index.php" class="logo">

			<!-- Logo normal -->
			<img
				src="/erpplus/public/assets/img/logo.png"
				class="logo-normal"
				alt="ERP Plus">

			<!-- Logo quando sidebar estiver minimizada -->
			<img
				src="/erpplus/public/assets/img/minilogo.png"
				class="logo-mini"
				alt="ERP Plus">

		</a>

	</div>
	<!-- /Logo -->


	<!-- Sidebar Toggle -->
	<a id="toggle_btn" href="javascript:void(0);">
		<span class="bar-icon">
			<span></span>
			<span></span>
			<span></span>
		</span>
	</a>


	<!-- Título -->
	<div class="page-title-box">
		<h3>ERP Plus - Sistema Integrado de Gestão Empresarial</h3>
	</div>


	<!-- Mobile -->
	<a id="mobile_btn" class="mobile_btn" href="#sidebar">
		<i class="fa fa-bars"></i>
	</a>


	<!-- Menu -->
	<ul class="nav user-menu">


		<!-- Utilizador -->
		<li class="nav-item dropdown has-arrow main-drop">

			<a href="#" class="dropdown-toggle nav-link" data-toggle="dropdown">

				<span class="user-img">

					<img src="<?= $avatar; ?>" alt="">

					<span class="status online"></span>

				</span>

				<span><?= htmlspecialchars($nome); ?></span>

			</a>

			<div class="dropdown-menu dropdown-menu-right">

				<a class="dropdown-item"
					href="/erpplus/index.php?page=perfil_usuario">

					<i class="la la-user mr-2"></i>
					Meu Perfil

				</a>

				<a class="dropdown-item"
					href="/erpplus/index.php?page=configuracoes">

					<i class="la la-cog mr-2"></i>
					Configurações

				</a>

				<div class="dropdown-divider"></div>

				<a
					class="dropdown-item text-danger"
					href="#"
					onclick="fazerLogout(); return false;">

					<i class="la la-sign-out mr-2"></i>
					Terminar Sessão

				</a>

			</div>

		</li>

	</ul>
	<!-- /Menu -->


	<!-- Mobile Menu -->
	<div class="dropdown mobile-user-menu">

		<a href="#"
			class="nav-link dropdown-toggle"
			data-toggle="dropdown">

			<i class="fa fa-ellipsis-v"></i>

		</a>

		<div class="dropdown-menu dropdown-menu-right">

			<a class="dropdown-item"
				href="/erpplus/index.php?page=perfil">

				Meu Perfil

			</a>

			<a class="dropdown-item"
				href="/erpplus/index.php?page=configuracoes">

				Configurações

			</a>

			<a
				class="dropdown-item text-danger"
				href="#"
				onclick="fazerLogout(); return false;">

				Sair

			</a>

		</div>

	</div>

</div>
<!-- /Header -->
<style>
	/* ==========================================
   LOGO DA SIDEBAR
========================================== */

	.header-left .logo {
		display: flex;
		align-items: center;
		justify-content: center;
		height: 60px;
	}


	.header-left .logo-normal {
		display: block;
		max-width: 135px;
		max-height: 48px;
		width: auto;
		height: auto;

		/* Baixar a imagem */
		transform: translateY(6px);
	}

	.header-left .logo-mini {
		display: none;
		width: 48px;
		height: 48px;
		object-fit: contain;

		/* Baixar a imagem */
		transform: translateY(15px);
	}


	/* ==========================================
   SIDEBAR MINIMIZADA
========================================== */

	body.mini-sidebar .header-left .logo-normal {
		display: none;
	}

	body.mini-sidebar .header-left .logo-mini {
		display: block;
	}


	/* Ajustar largura da área do logo */

	body.mini-sidebar .header-left {
		width: 70px;
	}
</style>