<?php
/**
 * Perfil do Usuário
 * Sistema de Gestão Financeira (SGF)
 */

$user_id = $_SESSION['usuario_id'];
$msg_erro = "";
$msg_sucesso = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['change_password'])) {
        $senha_atual = $_POST['senha_atual'] ?? '';
        $nova_senha = $_POST['nova_senha'] ?? '';
        $confirma_senha = $_POST['confirma_senha'] ?? '';

        // Busca senha atual do banco
        $stmt = $pdo->prepare("SELECT senha FROM usuarios WHERE id = ?");
        $stmt->execute([$user_id]);
        $hash_atual = $stmt->fetchColumn();

        if (!password_verify($senha_atual, $hash_atual)) {
            $msg_erro = "A senha atual está incorreta.";
        } elseif (strlen($nova_senha) < 6) {
            $msg_erro = "A nova senha deve ter pelo menos 6 caracteres.";
        } elseif ($nova_senha !== $confirma_senha) {
            $msg_erro = "A nova senha e a confirmação não coincidem.";
        } else {
            // Atualiza senha e remove flag de primeiro acesso
            $novo_hash = password_hash($nova_senha, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("UPDATE usuarios SET senha = ?, primeiro_acesso = FALSE WHERE id = ?");
            $stmt->execute([$novo_hash, $user_id]);
            
            $_SESSION['primeiro_acesso'] = false;
            $msg_sucesso = "Senha alterada com sucesso!";
        }
    }
}

// Exibe aviso se for primeiro acesso
if (isset($_GET['msg']) && $_GET['msg'] === 'trocar_senha') {
    $msg_atencao = "Este é o seu primeiro acesso. Por favor, altere sua senha para continuar com segurança.";
}
?>

<h3>Meu Perfil</h3>

<?php if (isset($msg_atencao)): ?>
    <div class="alert alert-danger" style="background: #fff3cd; color: #856404; border: 1px solid #ffeeba;">
        <i class="fas fa-exclamation-triangle"></i> <?= s($msg_atencao) ?>
    </div>
<?php endif; ?>

<?php if ($msg_erro): ?>
    <div class="alert alert-danger"><?= s($msg_erro) ?></div>
<?php endif; ?>

<?php if ($msg_sucesso): ?>
    <div class="alert alert-success"><?= s($msg_sucesso) ?></div>
<?php endif; ?>

<div class="card" style="max-width: 500px;">
    <h4>Alterar Senha</h4>
    <form action="index.php?page=perfil" method="POST" style="margin-top: 1.5rem;">
        <input type="hidden" name="csrf_token" value="<?= gerarCSRF() ?>">
        
        <div class="form-group">
            <label>Senha Atual</label>
            <input type="password" name="senha_atual" class="form-control" required>
        </div>

        <div class="form-group">
            <label>Nova Senha</label>
            <input type="password" name="nova_senha" class="form-control" required>
        </div>

        <div class="form-group">
            <label>Confirmar Nova Senha</label>
            <input type="password" name="confirma_senha" class="form-control" required>
        </div>

        <div style="margin-top: 1.5rem;">
            <button type="submit" name="change_password" class="btn btn-primary">Atualizar Senha</button>
        </div>
    </form>
</div>

<div class="card" style="max-width: 500px; margin-top: 2rem; background: #f8f9fa; border: none;">
    <h4>Informações da Conta</h4>
    <div style="margin-top: 1rem;">
        <p><strong>Nome:</strong> <?= s($_SESSION['usuario_nome']) ?></p>
        <p><strong>Cargo:</strong> <?= s($_SESSION['usuario_cargo'] === 'admin' ? 'Administrador' : 'Usuário') ?></p>
        <p><strong>ID:</strong> #<?= s($_SESSION['usuario_id']) ?></p>
    </div>
</div>
