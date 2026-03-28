<?php
/**
 * Gestão de Grupos (Apenas Admin)
 * Sistema de Gestão Financeira (SGF)
 */

exigirAdmin();

$msg = "";
$action = $_GET['action'] ?? 'list';
$edit_id = $_GET['id'] ?? null;

// Lógica de Post (Create/Update/Delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['save_group'])) {
        $id = $_POST['id'] ?? null;
        $nome = trim($_POST['nome'] ?? '');

        if (empty($nome)) {
            setMensagem("O nome do grupo é obrigatório.", "danger");
        } else {
            if ($id) {
                // Update
                $stmt = $pdo->prepare("UPDATE grupos SET nome = ? WHERE id = ?");
                $stmt->execute([$nome, $id]);
                setMensagem("Grupo atualizado!");
            } else {
                // Create
                $stmt = $pdo->prepare("INSERT INTO grupos (nome) VALUES (?)");
                $stmt->execute([$nome]);
                setMensagem("Grupo criado!");
            }
            redirecionar("index.php?page=grupos");
        }
    }

    if (isset($_POST['delete_group'])) {
        $id = $_POST['id'] ?? null;
        
        // Verifica se o grupo tem usuários (Não permite excluir se tiver)
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM usuarios WHERE grupo_id = ?");
        $stmt->execute([$id]);
        if ($stmt->fetchColumn() > 0) {
            setMensagem("Não é possível excluir um grupo que possui usuários vinculados.", "danger");
        } else {
            $stmt = $pdo->prepare("DELETE FROM grupos WHERE id = ?");
            $stmt->execute([$id]);
            setMensagem("Grupo excluído!");
        }
        redirecionar("index.php?page=grupos");
    }
}

// Dados para Edição
$edit_data = null;
if ($action === 'edit' && $edit_id) {
    $stmt = $pdo->prepare("SELECT * FROM grupos WHERE id = ?");
    $stmt->execute([$edit_id]);
    $edit_data = $stmt->fetch();
}

// Busca Grupos e Contagem de Usuários
$stmt = $pdo->query("SELECT g.*, (SELECT COUNT(*) FROM usuarios u WHERE u.grupo_id = g.id) as total_usuarios 
                     FROM grupos g ORDER BY nome ASC");
$grupos = $stmt->fetchAll();
?>

<div class="header-actions" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
    <h3>Gestão de Grupos</h3>
</div>

<div class="card">
    <h4><?= $edit_data ? 'Editar' : 'Novo' ?> Grupo</h4>
    <form action="index.php?page=grupos" method="POST" class="form-inline" style="margin-top: 1rem;">
        <input type="hidden" name="csrf_token" value="<?= gerarCSRF() ?>">
        <input type="hidden" name="id" value="<?= s($edit_data['id'] ?? '') ?>">

        <div class="form-group" style="flex: 2;">
            <label>Nome do Grupo</label>
            <input type="text" name="nome" class="form-control" value="<?= s($edit_data['nome'] ?? '') ?>" placeholder="Ex: Família Silva" required>
        </div>

        <div class="form-group">
            <button type="submit" name="save_group" class="btn btn-primary">
                <?= $edit_data ? 'Salvar Edição' : 'Criar Grupo' ?>
            </button>
            <?php if ($edit_data): ?>
                <a href="index.php?page=grupos" class="btn" style="background: #eee;">Cancelar</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<div class="card">
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nome do Grupo</th>
                    <th>Qtd. Usuários</th>
                    <th>Criado em</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($grupos as $g): ?>
                    <tr>
                        <td>#<?= $g['id'] ?></td>
                        <td style="font-weight: 500;"><?= s($g['nome']) ?></td>
                        <td>
                            <span class="badge" style="background: #f1f2f6; color: #2f3640; padding: 4px 10px;">
                                <?= $g['total_usuarios'] ?> usuári<?= $g['total_usuarios'] == 1 ? 'o' : 'os' ?>
                            </span>
                        </td>
                        <td><?= formatarData($g['criado_em']) ?></td>
                        <td>
                            <a href="index.php?page=grupos&action=edit&id=<?= $g['id'] ?>" title="Editar">
                                <i class="fas fa-edit" style="color: var(--accent);"></i>
                            </a>
                            &nbsp;
                            <form action="index.php?page=grupos" method="POST" style="display:inline;" onsubmit="return confirm('Excluir este grupo?');">
                                <input type="hidden" name="csrf_token" value="<?= gerarCSRF() ?>">
                                <input type="hidden" name="id" value="<?= $g['id'] ?>">
                                <button type="submit" name="delete_group" style="background:none; border:none; cursor:pointer; padding:0;" <?= $g['total_usuarios'] > 0 ? 'disabled title="Não pode excluir com usuários"' : '' ?>>
                                    <i class="fas fa-trash" style="color: <?= $g['total_usuarios'] > 0 ? '#bdc3c7' : 'var(--danger)' ?>;"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($grupos)): ?>
                    <tr><td colspan="5" style="text-align:center;">Nenhum grupo cadastrado.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
