<?php
/**
 * Top Barra Superior
 * Sistema de Gestão Financeira (SGF)
 */
?>
<header class="header">
    <div class="header-breadcrumb">
        <span style="color: var(--text-muted); font-size: 0.9rem;">SGF / </span>
        <span style="font-weight: 600; text-transform: capitalize;"><?= s($page_title) ?></span>
    </div>

    <div class="user-info">
        <div class="user-details" style="text-align: right;">
            <div class="user-name"><?= s($_SESSION['usuario_nome']) ?></div>
            <div class="user-role"><?= s($_SESSION['usuario_cargo'] === 'admin' ? 'Administrador' : 'Usuário') ?></div>
        </div>
        <a href="index.php?action=logout" class="logout-btn" title="Sair">
            Sair
        </a>
    </div>
</header>
