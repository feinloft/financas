<?php
/**
 * Gestão de Despesas
 * Sistema de Gestão Financeira (SGF)
 */

$user_id = $_SESSION['usuario_id'];
$grupo_id = getGrupoId();
$is_admin = ehAdmin();
$mes_filtro = $_GET['mes'] ?? date('m');
$ano_filtro = $_GET['ano'] ?? date('Y');
$cat_filtro = $_GET['categoria'] ?? '';

// Lógica de Post (Create/Update/Delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['save_movimentacao'])) {
        $id = $_POST['id'] ?? null;
        $data = $_POST['data'] ?? date('Y-m-d');
        $descricao = trim($_POST['descricao'] ?? '');
        $categoria_id = $_POST['categoria_id'] ?? null;
        $valor = str_replace(',', '.', $_POST['valor'] ?? '0');
        $observacoes = $_POST['observacoes'] ?? '';

        if (empty($descricao) || empty($categoria_id) || empty($valor)) {
            setMensagem("Preencha os campos obrigatórios.", "danger");
        } else {
            if ($id) {
                $stmt = $pdo->prepare("UPDATE movimentacoes SET data = ?, descricao = ?, categoria_id = ?, valor = ?, observacoes = ? WHERE id = ? AND (grupo_id = ? OR ? = 1)");
                $stmt->execute([$data, $descricao, $categoria_id, $valor, $observacoes, $id, $grupo_id, $is_admin]);
                setMensagem("Despesa atualizada!");
            } else {
                $stmt = $pdo->prepare("INSERT INTO movimentacoes (usuario_id, grupo_id, categoria_id, tipo, data, valor, descricao, observacoes) VALUES (?, ?, ?, 'despesa', ?, ?, ?, ?)");
                $stmt->execute([$user_id, $grupo_id, $categoria_id, $data, $valor, $descricao, $observacoes]);
                setMensagem("Despesa adicionada!");
            }
            redirecionar("index.php?page=despesas&mes=$mes_filtro&ano=$ano_filtro");
        }
    }

    if (isset($_POST['delete_movimentacao'])) {
        $id = $_POST['id'] ?? null;
        $stmt = $pdo->prepare("DELETE FROM movimentacoes WHERE id = ? AND (grupo_id = ? OR ? = 1)");
        $stmt->execute([$id, $grupo_id, $is_admin]);
        setMensagem("Despesa removida!");
        redirecionar("index.php?page=despesas&mes=$mes_filtro&ano=$ano_filtro");
    }
}

// Dados para Edição
$edit_data = null;
if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
    $stmt = $pdo->prepare("SELECT * FROM movimentacoes WHERE id = ? AND (grupo_id = ? OR ? = 1)");
    $stmt->execute([$_GET['id'], $grupo_id, $is_admin]);
    $edit_data = $stmt->fetch();
}

// Busca Categorias de Despesa (Do grupo ou Globais se implementado)
$stmt = $pdo->prepare("SELECT * FROM categorias WHERE tipo IN ('despesa', 'ambos') AND (grupo_id = ? OR ? = 1 OR grupo_id IS NULL) ORDER BY nome ASC");
$stmt->execute([$grupo_id, $is_admin]);
$categorias = $stmt->fetchAll();

// Busca Despesas com Filtros
$query = "SELECT m.*, c.nome as categoria_nome, c.cor as categoria_cor 
          FROM movimentacoes m 
          LEFT JOIN categorias c ON m.categoria_id = c.id 
          WHERE (? = 1 OR m.grupo_id = ?) AND m.tipo = 'despesa' 
          AND MONTH(m.data) = ? AND YEAR(m.data) = ?";
$params = [$is_admin, $grupo_id, $mes_filtro, $ano_filtro];

if ($cat_filtro) {
    $query .= " AND m.categoria_id = ?";
    $params[] = $cat_filtro;
}

$query .= " ORDER BY m.data DESC, m.id DESC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$despesas = $stmt->fetchAll();
?>

<div class="header-actions" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
    <h3>Despesas</h3>
    <a href="index.php?page=despesas&action=new" class="btn btn-danger">Nova Despesa</a>
</div>

<?php if (isset($_GET['action']) && ($_GET['action'] === 'new' || $_GET['action'] === 'edit')): ?>
<div class="card">
    <h4><?= $_GET['action'] === 'edit' ? 'Editar' : 'Nova' ?> Despesa</h4>
    <form action="index.php?page=despesas&mes=<?= $mes_filtro ?>&ano=<?= $ano_filtro ?>" method="POST" class="form-inline" style="margin-top: 1rem;">
        <input type="hidden" name="csrf_token" value="<?= gerarCSRF() ?>">
        <input type="hidden" name="id" value="<?= s($edit_data['id'] ?? '') ?>">

        <div class="form-group" style="flex: 1; min-width: 150px;">
            <label>Data</label>
            <input type="date" name="data" class="form-control" value="<?= s($edit_data['data'] ?? date('Y-m-d')) ?>" required>
        </div>

        <div class="form-group" style="flex: 2; min-width: 200px;">
            <label>Descrição</label>
            <input type="text" name="descricao" class="form-control" value="<?= s($edit_data['descricao'] ?? '') ?>" placeholder="Ex: Supermercado" required>
        </div>

        <div class="form-group" style="flex: 1; min-width: 150px;">
            <label>Categoria</label>
            <select name="categoria_id" class="form-control" required>
                <option value="">Selecione...</option>
                <?php foreach ($categorias as $cat): ?>
                    <option value="<?= $cat['id'] ?>" <?= ($edit_data['categoria_id'] ?? '') == $cat['id'] ? 'selected' : '' ?>>
                        <?= s($cat['nome']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group" style="flex: 1; min-width: 120px;">
            <label>Valor (R$)</label>
            <input type="number" step="0.01" name="valor" class="form-control" value="<?= s($edit_data['valor'] ?? '') ?>" required>
        </div>

        <div class="form-group" style="flex: 2; min-width: 200px;">
            <label>Observações</label>
            <input type="text" name="observacoes" class="form-control" value="<?= s($edit_data['observacoes'] ?? '') ?>">
        </div>

        <div class="form-group">
            <button type="submit" name="save_movimentacao" class="btn btn-primary">Salvar</button>
            <a href="index.php?page=despesas&mes=<?= $mes_filtro ?>&ano=<?= $ano_filtro ?>" class="btn" style="background: #eee;">Cancelar</a>
        </div>
    </form>
</div>
<?php endif; ?>

<div class="card">
    <form action="index.php" method="GET" class="form-inline">
        <input type="hidden" name="page" value="despesas">
        
        <div class="form-group">
            <label>Mês</label>
            <select name="mes" class="form-control">
                <?php for($m=1; $m<=12; $m++): ?>
                    <option value="<?= sprintf('%02d', $m) ?>" <?= $mes_filtro == $m ? 'selected' : '' ?>>
                        <?= date('F', mktime(0, 0, 0, $m, 1)) ?>
                    </option>
                <?php endfor; ?>
            </select>
        </div>

        <div class="form-group">
            <label>Ano</label>
            <select name="ano" class="form-control">
                <?php for($y=date('Y')-5; $y<=date('Y')+1; $y++): ?>
                    <option value="<?= $y ?>" <?= $ano_filtro == $y ? 'selected' : '' ?>><?= $y ?></option>
                <?php endfor; ?>
            </select>
        </div>

        <div class="form-group">
            <label>Categoria</label>
            <select name="categoria" class="form-control">
                <option value="">Todas</option>
                <?php foreach ($categorias as $cat): ?>
                    <option value="<?= $cat['id'] ?>" <?= $cat_filtro == $cat['id'] ? 'selected' : '' ?>>
                        <?= s($cat['nome']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <button type="submit" class="btn btn-primary">Filtrar</button>
        </div>
    </form>

    <div class="table-responsive" style="margin-top: 2rem;">
        <table>
            <thead>
                <tr>
                    <th>Data</th>
                    <th>Descrição</th>
                    <th>Categoria</th>
                    <th>Valor</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php $total_page = 0; ?>
                <?php foreach ($despesas as $r): $total_page += $r['valor']; ?>
                    <tr>
                        <td><?= formatarData($r['data']) ?></td>
                        <td>
                            <?= s($r['descricao']) ?>
                            <?php if ($r['observacoes']): ?>
                                <br><small style="color: #999;"><?= s($r['observacoes']) ?></small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span style="background: <?= s($r['categoria_cor']) ?>; color: white; padding: 2px 8px; border-radius: 4px; font-size: 0.8rem;">
                                <?= s($r['categoria_nome']) ?>
                            </span>
                        </td>
                        <td style="color: var(--danger); font-weight: 600;"><?= formatarMoeda($r['valor']) ?></td>
                        <td>
                            <a href="index.php?page=despesas&action=edit&id=<?= $r['id'] ?>&mes=<?= $mes_filtro ?>&ano=<?= $ano_filtro ?>">
                                <i class="fas fa-edit" style="color: var(--accent);"></i>
                            </a>
                            &nbsp;
                            <form action="index.php?page=despesas&mes=<?= $mes_filtro ?>&ano=<?= $ano_filtro ?>" method="POST" style="display:inline;" onsubmit="return confirm('Excluir esta despesa?');">
                                <input type="hidden" name="csrf_token" value="<?= gerarCSRF() ?>">
                                <input type="hidden" name="id" value="<?= $r['id'] ?>">
                                <button type="submit" name="delete_movimentacao" style="background:none; border:none; cursor:pointer;">
                                    <i class="fas fa-trash" style="color: var(--danger);"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($despesas)): ?>
                    <tr><td colspan="5" style="text-align:center;">Nenhuma despesa encontrada para este período.</td></tr>
                <?php else: ?>
                    <tr style="background: #f8f9fa; font-weight: bold;">
                        <td colspan="3" style="text-align: right;">Total do Período:</td>
                        <td colspan="2" style="color: var(--danger);"><?= formatarMoeda($total_page) ?></td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
