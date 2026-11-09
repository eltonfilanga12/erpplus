<?php

$menus = Auth::menu();

$menuPai = [];
$subMenus = [];

/*
|--------------------------------------------------------------------------
| Organiza os menus
|--------------------------------------------------------------------------
*/

foreach ($menus as $menu) {

    if (empty($menu['menu_pai'])) {

        $menuPai[$menu['id']] = $menu;
    } else {

        $subMenus[$menu['menu_pai']][] = $menu;
    }
}

?>

<div class="sidebar" id="sidebar">

    <div class="sidebar-inner slimscroll">

        <div id="sidebar-menu" class="sidebar-menu">

            <ul>

                <?php foreach ($menuPai as $pai): ?>

                    <?php if (!isset($subMenus[$pai['id']])): ?>

                        <li>

                            <a href="index.php?page=<?= $pai['rota'] ?>">

                               <i class="<?= $pai['icone'] ?>"></i>

                                <span><?= htmlspecialchars($pai['titulo']) ?></span>

                            </a>

                        </li>

                    <?php else: ?>

                        <li class="submenu">

                            <a href="#">

                                <i class="<?= $pai['icone'] ?>"></i>

                                <span><?= htmlspecialchars($pai['titulo']) ?></span>

                                <span class="menu-arrow"></span>

                            </a>

                            <ul style="display:none;">

                                <?php foreach ($subMenus[$pai['id']] as $filho): ?>

                                    <li>

                                        <a href="index.php?page=<?= $filho['rota'] ?>">

                                            <?= htmlspecialchars($filho['titulo']) ?>

                                        </a>

                                    </li>

                                <?php endforeach; ?>

                            </ul>

                        </li>

                    <?php endif; ?>

                <?php endforeach; ?>

            </ul>

        </div>

    </div>

</div>