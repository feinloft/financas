<?php
/**
 * Menu Lateral (Sidebar)
 * Sistema de Gestão Financeira (SGF)
 */
$current_page = $_GET['page'] ?? 'dashboard';
?>
<aside class="sidebar">
    <div class="sidebar-header">
        <h2>SGF</h2>
    </div>
    
    <ul class="sidebar-menu">
        <li class="<?= $current_page === 'dashboard' ? 'active' : '' ?>">
            <a href="index.php?page=dashboard">
                <i class="fas fa-chart-line"></i> Dashboard
            </a>
        </li>
        <li class="<?= $current_page === 'receitas' ? 'active' : '' ?>">
            <a href="index.php?page=receitas">
                <i class="fas fa-hand-holding-usd"></i> Receitas
            </a>
        </li>
        <li class="<?= $current_page === 'despesas' ? 'active' : '' ?>">
            <a href="index.php?page=despesas">
                <i class="fas fa-wallet"></i> Despesas
            </a>
        </li>
        <li class="<?= $current_page === 'categorias' ? 'active' : '' ?>">
            <a href="index.php?page=categorias">
                <i class="fas fa-tags"></i> Categorias
            </a>
        </li>
        <li class="<?= $current_page === 'fluxo' ? 'active' : '' ?>">
            <a href="index.php?page=fluxo">
                <i class="fas fa-history"></i> Fluxo de Caixa
            </a>
        </li>
        
        <?php if (ehAdmin()): ?>
            <li class="<?= $current_page === 'usuarios' ? 'active' : '' ?>">
                <a href="index.php?page=usuarios" style="color: #3498db;">
                    <i class="fas fa-users-cog"></i> Usuários (Admin)
                </a>
            </li>
            <li class="<?= $current_page === 'grupos' ? 'active' : '' ?>">
                <a href="index.php?page=grupos" style="color: #3498db;">
                    <i class="fas fa-layer-group"></i> Grupos (Admin)
                </a>
            </li>
        <?php endif; ?>
    </ul>

    <div class="sidebar-footer" style="padding: 1rem; border-top: 1px solid rgba(255, 255, 255, 0.1); font-size: 0.7rem; color: #7f8c8d; text-align: center;">
        Versão 1.0.0
    </div>
</aside>
