<?php
/**
 * Funções Auxiliares
 * Sistema de Gestão Financeira (SGF)
 */

/**
 * Sanitiza saída para evitar XSS
 */
function s($texto) {
    return htmlspecialchars($texto ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Formata valor para moeda Real (BRL)
 */
function formatarMoeda($valor) {
    return 'R$ ' . number_format($valor, 2, ',', '.');
}

/**
 * Formata data de YYYY-MM-DD para DD/MM/YYYY
 */
function formatarData($data) {
    return date('d/m/Y', strtotime($data));
}

/**
 * Gera um token CSRF e armazena na sessão
 */
function gerarCSRF() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Valida o token CSRF enviado via POST
 */
function validarCSRF($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Redireciona para uma URL específica
 */
function redirecionar($url) {
    header("Location: $url");
    exit;
}

/**
 * Exibe um alerta SweetAlert (JS) ou simples se necessário
 * (Neste projeto utilizaremos alertas simplificados via URL ou sessão)
 */
function setMensagem($texto, $tipo = 'success') {
    $_SESSION['mensagem'] = ['texto' => $texto, 'tipo' => $tipo];
}

function getMensagem() {
    if (isset($_SESSION['mensagem'])) {
        $msg = $_SESSION['mensagem'];
        unset($_SESSION['mensagem']);
        return $msg;
    }
    return null;
}
