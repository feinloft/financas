<?php
/**
 * Router Principal (Entry point)
 * Sistema de Gestão Financeira (SGF)
 */

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

// Exige login para todas as páginas (exceto login.php que é acessado diretamente)
exigirLogin();

// Logica de Logout
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    logout();
}

// Router - Captura a página solicitada
$page = $_GET['page'] ?? 'dashboard';

// Lista de páginas permitidas
$allowed_pages = [
    'dashboard',
    'receitas',
    'despesas',
    'categorias',
    'fluxo',
    'usuarios', // Admin check inside the file or here
    'grupos',   // Admin check inside the file or here
    'perfil'    // Para troca de senha inicial
];

// Validação simples da página
if (!in_array($page, $allowed_pages)) {
    $page = 'dashboard';
}

// Bloqueio de acesso administrativo para usuários comuns
if ($page === 'usuarios' || $page === 'grupos') {
    exigirAdmin();
}

// Título amigável da página (pt-BR)
$titles = [
    'dashboard' => 'Dashboard',
    'receitas' => 'Receitas',
    'despesas' => 'Despesas',
    'categorias' => 'Categorias',
    'fluxo' => 'Fluxo de Caixa',
    'usuarios' => 'Gestão de Usuários',
    'grupos' => 'Gestão de Grupos',
    'perfil' => 'Meu Perfil'
];
$page_title = $titles[$page] ?? 'SGF';

// Inicia captura de conteúdo para renderizar dentro do layout
ob_start();

$page_file = __DIR__ . "/pages/$page.php";
if (file_exists($page_file)) {
    include $page_file;
} else {
    echo "<div class='alert alert-danger'>Página não encontrada: " . s($page) . "</div>";
}

$page_content = ob_get_clean();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= s($page_title) ?> - SGF</title>
    
    <!-- CSS Standard -->
    <link rel="stylesheet" href="assets/css/style.css">
    
    <!-- Font Awesome (Ícones) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Chart.js CDN (Para o Dashboard) -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="layout">
        <!-- Sidebar Navigation -->
        <?php include __DIR__ . '/includes/sidebar.php'; ?>
        
        <main class="main">
            <!-- Header (Barra Superior) -->
            <?php include __DIR__ . '/includes/header.php'; ?>
            
            <div class="content">
                <!-- Mensagens Globais (Sucesso/Erro) -->
                <?php $msg = getMensagem(); if ($msg): ?>
                    <div class="alert alert-<?= s($msg['tipo']) ?>">
                        <?= s($msg['texto']) ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($_GET['error']) && $_GET['error'] === 'acesso_negado'): ?>
                    <div class="alert alert-danger">
                        Acesso negado: Você não tem permissão para acessar esta área.
                    </div>
                <?php endif; ?>

                <!-- Conteúdo Dinâmico -->
                <?= $page_content ?>
            </div>
        </main>
    </div>
    
    <script>
        // JS global, se necessário
    </script>
</body>
</html>
