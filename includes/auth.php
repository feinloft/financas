<?php
/**
 * Lógica de Autenticação e Sessão
 * Sistema de Gestão Financeira (SGF)
 */

session_start();

/**
 * Regenera o ID da sessão para prevenir Session Fixation
 */
function regenerarSessao() {
    session_regenerate_id(true);
}

/**
 * Verifica se o usuário está logado
 */
function estaLogado() {
    return isset($_SESSION['usuario_id']);
}

/**
 * Exige que o usuário esteja logado
 */
function exigirLogin() {
    if (!estaLogado()) {
        header("Location: login.php");
        exit;
    }
}

/**
 * Verifica se o usuário é administrador
 */
function ehAdmin() {
    return isset($_SESSION['usuario_cargo']) && $_SESSION['usuario_cargo'] === 'admin';
}

/**
 * Exige que o usuário seja administrador
 */
function exigirAdmin() {
    exigirLogin();
    if (!ehAdmin()) {
        // Redireciona para o dashboard com mensagem de erro se tentar acessar área administrativa
        header("Location: index.php?page=dashboard&error=acesso_negado");
        exit;
    }
}

/**
 * Retorna o ID do grupo do usuário logado
 */
function getGrupoId() {
    return $_SESSION['usuario_grupo_id'] ?? null;
}

/**
 * Realiza o logout do usuário
 */
function logout() {
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
    header("Location: login.php");
    exit;
}

// Verifica CSRF em requisições POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Páginas que NÃO precisam de CSRF (geralmente logout se for GET, ou login se o token ainda não existir)
    // Mas o login deve gerar um se o usuário já estiver na página.
    // Vamos exigir CSRF em todos os POSTs, exceto no próprio login se preferir, 
    // mas o melhor é incluir no formulário de login também.
    
    if (isset($_POST['csrf_token'])) {
        require_once __DIR__ . '/functions.php';
        if (!validarCSRF($_POST['csrf_token'])) {
            die("Erro de validação CSRF. Requisição inválida.");
        }
    }
}
