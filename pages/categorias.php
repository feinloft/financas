<?php
/**
 * Gestão de Categorias
 * Sistema de Gestão Financeira (SGF)
 */

$msg = "";
$grupo_id = getGrupoId();
$is_admin = ehAdmin();
$action = $_GET['action'] ?? 'list';
$edit_id = $_GET['id'] ?? null;

// Lógica de Post (Create/Update/Delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['save_category'])) {
        $id = $_POST['id'] ?? null;
        $nome = trim($_POST['nome'] ?? '');
        $tipo = $_POST['tipo'] ?? 'ambos';
        $cor = $_POST['cor'] ?? '#3498db';

        if (empty($nome)) {
            setMensagem("O nome da categoria é obrigatório.", "danger");
        } else {
            if ($id) {
                // Update
                $stmt = $pdo->prepare("UPDATE categorias SET nome = ?, tipo = ?, cor = ? WHERE id = ? AND (grupo_id = ? OR ? = 1)");
                $stmt->execute([$nome, $tipo, $cor, $id, $grupo_id, $is_admin]);
                setMensagem("Categoria atualizada com sucesso!");
            } else {
                // Create
                $stmt = $pdo->prepare("INSERT INTO categorias (nome, tipo, cor, grupo_id) VALUES (?, ?, ?, ?)");
                $stmt->execute([$nome, $tipo, $cor, $grupo_id]);
                setMensagem("Categoria criada com sucesso!");
            }
            redirecionar("index.php?page=categorias");
        }
    }

    if (isset($_POST['delete_category'])) {
        $id = $_POST['id'] ?? null;
        // Verifica se a categoria está em uso
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM movimentacoes WHERE categoria_id = ?");
        $stmt->execute([$id]);
        if ($stmt->fetchColumn() > 0) {
            setMensagem("Não é possível excluir uma categoria que possui movimentações associadas.", "danger");
        } else {
            $stmt = $pdo->prepare("DELETE FROM categorias WHERE id = ? AND (grupo_id = ? OR ? = 1)");
            $stmt->execute([$id, $grupo_id, $is_admin]);
            setMensagem("Categoria excluída com sucesso!");
        }
        redirecionar("index.php?page=categorias");
    }
}

if ($action === 'edit' && $edit_id) {
    $stmt = $pdo->prepare("SELECT * FROM categorias WHERE id = ? AND (grupo_id = ? OR ? = 1)");
    $stmt->execute([$edit_id, $grupo_id, $is_admin]);
    $edit_data = $stmt->fetch();
}

// Busca Categorias
$stmt = $pdo->prepare("SELECT * FROM categorias WHERE (grupo_id = ? OR ? = 1 OR grupo_id IS NULL) ORDER BY nome ASC");
$stmt->execute([$grupo_id, $is_admin]);
$categorias = $stmt->fetchAll();
?>

<h3>Gerenciar Categorias</h3>

<div class="card">
    <form action="index.php?page=categorias" method="POST" class="form-inline">
        <input type="hidden" name="csrf_token" value="<?= gerarCSRF() ?>">
        <input type="hidden" name="id" value="<?= s($edit_data['id'] ?? '') ?>">

        <div class="form-group">
            <label>Nome</label>
            <input type="text" name="nome" class="form-control" value="<?= s($edit_data['nome'] ?? '') ?>" required>
        </div>

        <div class="form-group">
            <label>Tipo</label>
            <select name="tipo" class="form-control" required>
                <option value="receita" <?= ($edit_data['tipo'] ?? '') === 'receita' ? 'selected' : '' ?>>Receita</option>
                <option value="despesa" <?= ($edit_data['tipo'] ?? '') === 'despesa' ? 'selected' : '' ?>>Despesa</option>
                <option value="ambos" <?= ($edit_data['tipo'] ?? 'ambos') === 'ambos' ? 'selected' : '' ?>>Ambos</option>
            </select>
        </div>

        <div class="form-group">
            <label>Cor</label>
            <input type="color" name="cor" class="form-control" style="height: 38px; width: 60px; padding: 2px;" value="<?= s($edit_data['cor'] ?? '#3498db') ?>">
        </div>

        <div class="form-group">
            <button type="submit" name="save_category" class="btn btn-primary">
                <?= $edit_data ? 'Salvar Edição' : 'Adicionar Categoria' ?>
            </button>
            <?php if ($edit_data): ?>
                <a href="index.php?page=categorias" class="btn" style="background: #eee;">Cancelar</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<div class="card">
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Cor</th>
                    <th>Nome</th>
                    <th>Tipo</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($categorias as $c): ?>
                    <tr>
                        <td width="50">
                            <div style="width: 20px; height: 20px; border-radius: 50%; background-color: <?= s($c['cor']) ?>;"></div>
                        </td>
                        <td><?= s($c['nome']) ?></td>
                        <td>
                            <span style="text-transform: capitalize;"><?= s($c['tipo'] === 'ambos' ? 'Compartilhada' : $c['tipo']) ?></span>
                        </td>
                        <td>
                            <a href="index.php?page=categorias&action=edit&id=<?= $c['id'] ?>" title="Editar">
                                <i class="fas fa-edit" style="color: var(--accent);"></i>
                            </a>
                            &nbsp;
                            <form action="index.php?page=categorias" method="POST" style="display:inline;" onsubmit="return confirm('Excluir esta categoria?');">
                                <input type="hidden" name="csrf_token" value="<?= gerarCSRF() ?>">
                                <input type="hidden" name="id" value="<?= $c['id'] ?>">
                                <button type="submit" name="delete_category" style="background:none; border:none; cursor:pointer; padding:0;">
                                    <i class="fas fa-trash" style="color: var(--danger);"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($categorias)): ?>
                    <tr><td colspan="4" style="text-align:center;">Nenhuma categoria encontrada.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
