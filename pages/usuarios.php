<?php
/**
 * Gestão de Usuários (Apenas Admin)
 * Sistema de Gestão Financeira (SGF)
 */

exigirAdmin();

$action = $_GET['action'] ?? 'list';
$user_edit_id = $_GET['id'] ?? null;

// Lógica de Post (Create/Update/Status/Reset)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['save_user'])) {
        $id = $_POST['id'] ?? null;
        $nome = trim($_POST['nome'] ?? '');
        $usuario = trim($_POST['usuario'] ?? '');
        $cargo = $_POST['cargo'] ?? 'user';
        $grupo_id_input = $_POST['grupo_id'] ?? null;
        $senha = $_POST['senha'] ?? '';

        if (empty($nome) || empty($usuario)) {
            setMensagem("Nome e Usuário são obrigatórios.", "danger");
        } else {
            if ($id) {
                // Update (sem troca de senha por aqui)
                $stmt = $pdo->prepare("UPDATE usuarios SET nome = ?, usuario = ?, cargo = ?, grupo_id = ? WHERE id = ?");
                $stmt->execute([$nome, $usuario, $cargo, $grupo_id_input, $id]);
                setMensagem("Usuário atualizado com sucesso!");
            } else {
                // Create
                if (empty($senha)) {
                    setMensagem("A senha é obrigatória para novos usuários.", "danger");
                } else {
                    $hash = password_hash($senha, PASSWORD_BCRYPT);
                    $stmt = $pdo->prepare("INSERT INTO usuarios (nome, usuario, senha, cargo, grupo_id) VALUES (?, ?, ?, ?, ?)");
                    try {
                        $stmt->execute([$nome, $usuario, $hash, $cargo, $grupo_id_input]);
                        setMensagem("Usuário criado com sucesso!");
                    } catch (PDOException $e) {
                        setMensagem("Erro: Usuário já existe.", "danger");
                    }
                }
            }
            redirecionar("index.php?page=usuarios");
        }
    }

    if (isset($_POST['toggle_status'])) {
        $id = $_POST['id'] ?? null;
        $status = $_POST['status'] ?? 'ativo';
        $novo_status = ($status === 'ativo') ? 'inativo' : 'ativo';
        
        // Impede de desativar a si mesmo
        if ($id == $_SESSION['usuario_id']) {
            setMensagem("Você não pode desativar sua própria conta.", "danger");
        } else {
            $stmt = $pdo->prepare("UPDATE usuarios SET status = ? WHERE id = ?");
            $stmt->execute([$novo_status, $id]);
            setMensagem("Status do usuário alterado!");
        }
        redirecionar("index.php?page=usuarios");
    }

    if (isset($_POST['reset_password'])) {
        $id = $_POST['id'] ?? null;
        $nova_senha = $_POST['nova_senha'] ?? '';
        
        if (empty($nova_senha)) {
            setMensagem("A nova senha não pode estar em branco.", "danger");
        } else {
            $hash = password_hash($nova_senha, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("UPDATE usuarios SET senha = ?, primeiro_acesso = TRUE WHERE id = ?");
            $stmt->execute([$hash, $id]);
            setMensagem("Senha resetada com sucesso! O usuário deverá trocá-la no próximo acesso.");
        }
        redirecionar("index.php?page=usuarios");
    }
}

// Dados para Edição
$user_edit_data = null;
if ($action === 'edit' && $user_edit_id) {
    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
    $stmt->execute([$user_edit_id]);
    $user_edit_data = $stmt->fetch();
}

// Busca todos os usuários com o nome do grupo
$stmt = $pdo->query("SELECT u.*, g.nome as grupo_nome 
                     FROM usuarios u 
                     LEFT JOIN grupos g ON u.grupo_id = g.id 
                     ORDER BY u.nome ASC");
$usuarios = $stmt->fetchAll();

// Busca todos os grupos para o select
$stmt = $pdo->query("SELECT * FROM grupos ORDER BY nome ASC");
$lista_grupos = $stmt->fetchAll();
?>

<div class="header-actions" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
    <h3>Gestão de Usuários</h3>
    <?php if ($action !== 'edit' && $action !== 'new'): ?>
        <a href="index.php?page=usuarios&action=new" class="btn btn-primary">Novo Usuário</a>
    <?php endif; ?>
</div>

<?php if ($action === 'new' || $action === 'edit'): ?>
<div class="card">
    <h4><?= $action === 'edit' ? 'Editar' : 'Novo' ?> Usuário</h4>
    <form action="index.php?page=usuarios" method="POST" class="form-inline" style="margin-top: 1rem;">
        <input type="hidden" name="csrf_token" value="<?= gerarCSRF() ?>">
        <input type="hidden" name="id" value="<?= s($user_edit_data['id'] ?? '') ?>">

        <div class="form-group">
            <label>Nome Completo</label>
            <input type="text" name="nome" class="form-control" value="<?= s($user_edit_data['nome'] ?? '') ?>" required>
        </div>

        <div class="form-group">
            <label>Login (Usuário)</label>
            <input type="text" name="usuario" class="form-control" value="<?= s($user_edit_data['usuario'] ?? '') ?>" required>
        </div>

        <div class="form-group">
            <label>Cargo</label>
            <select name="cargo" class="form-control" required>
                <option value="user" <?= ($user_edit_data['cargo'] ?? '') === 'user' ? 'selected' : '' ?>>Usuário Comum</option>
                <option value="admin" <?= ($user_edit_data['cargo'] ?? '') === 'admin' ? 'selected' : '' ?>>Administrador</option>
            </select>
        </div>

        <div class="form-group">
            <label>Grupo</label>
            <select name="grupo_id" class="form-control" required>
                <option value="">Selecione um grupo...</option>
                <?php foreach ($lista_grupos as $g): ?>
                    <option value="<?= $g['id'] ?>" <?= ($user_edit_data['grupo_id'] ?? '') == $g['id'] ? 'selected' : '' ?>>
                        <?= s($g['nome']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <?php if ($action === 'new'): ?>
            <div class="form-group">
                <label>Senha Inicial</label>
                <input type="password" name="senha" class="form-control" required>
            </div>
        <?php endif; ?>

        <div class="form-group">
            <button type="submit" name="save_user" class="btn btn-success">Salvar</button>
            <a href="index.php?page=usuarios" class="btn" style="background: #eee;">Cancelar</a>
        </div>
    </form>
</div>
<?php endif; ?>

<div class="card">
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>Usuário</th>
                    <th>Cargo / Grupo</th>
                    <th>Status</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($usuarios as $u): ?>
                    <tr>
                        <td style="font-weight: 500;"><?= s($u['nome']) ?></td>
                        <td><?= s($u['usuario']) ?></td>
                        <td>
                            <span class="user-role" style="background: <?= $u['cargo'] === 'admin' ? '#d1ecf1' : '#f8f9fa' ?>;">
                                <?= s($u['cargo']) ?>
                            </span>
                            <br>
                            <small style="color: #666;"><i class="fas fa-users"></i> <?= s($u['grupo_nome'] ?? 'Sem Grupo') ?></small>
                        </td>
                        <td>
                            <span class="badge" style="color: <?= $u['status'] === 'ativo' ? 'var(--success)' : 'var(--danger)' ?>; font-weight: bold;">
                                <?= strtoupper(s($u['status'])) ?>
                            </span>
                        </td>
                        <td>
                            <a href="index.php?page=usuarios&action=edit&id=<?= $u['id'] ?>" title="Editar">
                                <i class="fas fa-edit" style="color: var(--accent);"></i>
                            </a>
                            &nbsp;
                            <form action="index.php?page=usuarios" method="POST" style="display:inline;" onsubmit="return confirm('Alterar status deste usuário?');">
                                <input type="hidden" name="csrf_token" value="<?= gerarCSRF() ?>">
                                <input type="hidden" name="id" value="<?= $u['id'] ?>">
                                <input type="hidden" name="status" value="<?= $u['status'] ?>">
                                <button type="submit" name="toggle_status" style="background:none; border:none; cursor:pointer;" title="<?= $u['status'] === 'ativo' ? 'Desativar' : 'Ativar' ?>">
                                    <i class="fas <?= $u['status'] === 'ativo' ? 'fa-user-slash' : 'fa-user-check' ?>" style="color: <?= $u['status'] === 'ativo' ? 'var(--danger)' : 'var(--success)' ?>;"></i>
                                </button>
                            </form>
                            &nbsp;
                            <button onclick="document.getElementById('modal-reset-<?= $u['id'] ?>').style.display='flex'" style="background:none; border:none; cursor:pointer;" title="Resetar Senha">
                                <i class="fas fa-key" style="color: var(--warning);"></i>
                            </button>

                            <!-- Modal Reset Senha (Simples) -->
                            <div id="modal-reset-<?= $u['id'] ?>" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); justify-content:center; align-items:center; z-index:1000;">
                                <div class="card" style="width: 300px; margin-bottom:0;">
                                    <h4>Resetar Senha: <?= s($u['usuario']) ?></h4>
                                    <form action="index.php?page=usuarios" method="POST" style="margin-top:1rem;">
                                        <input type="hidden" name="csrf_token" value="<?= gerarCSRF() ?>">
                                        <input type="hidden" name="id" value="<?= $u['id'] ?>">
                                        <div class="form-group">
                                            <label>Nova Senha</label>
                                            <input type="password" name="nova_senha" class="form-control" required>
                                        </div>
                                        <div style="display:flex; justify-content:flex-end; gap:10px;">
                                            <button type="button" onclick="this.closest('#modal-reset-<?= $u['id'] ?>').style.display='none'" class="btn" style="background:#eee;">Cancelar</button>
                                            <button type="submit" name="reset_password" class="btn btn-danger">Confirmar</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
