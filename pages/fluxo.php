<?php
/**
 * Relatório de Fluxo de Caixa
 * Sistema de Gestão Financeira (SGF)
 */

$user_id = $_SESSION['usuario_id'];
$grupo_id = getGrupoId();
$is_admin = ehAdmin();
$ano_filtro = $_GET['ano'] ?? date('Y');

// 1. Coleta dados mensais
$meses_nomes = [
    '01' => 'Janeiro', '02' => 'Fevereiro', '03' => 'Março', 
    '04' => 'Abril', '05' => 'Maio', '06' => 'Junho', 
    '07' => 'Julho', '08' => 'Agosto', '09' => 'Setembro', 
    '10' => 'Outubro', '11' => 'Novembro', '12' => 'Dezembro'
];

$relatorio = [];
$saldo_acumulado = 0;

// Busca saldo anterior (antes do ano selecionado)
$stmt = $pdo->prepare("SELECT 
    SUM(CASE WHEN tipo = 'receita' THEN valor ELSE -valor END) as anterior 
    FROM movimentacoes WHERE (? = 1 OR grupo_id = ?) AND YEAR(data) < ?");
$stmt->execute([$is_admin, $grupo_id, $ano_filtro]);
$saldo_acumulado = $stmt->fetchColumn() ?: 0;

for ($m = 1; $m <= 12; $m++) {
    $mes_str = sprintf('%02d', $m);
    
    $stmt = $pdo->prepare("SELECT 
        SUM(CASE WHEN tipo = 'receita' THEN valor ELSE 0 END) as receitas,
        SUM(CASE WHEN tipo = 'despesa' THEN valor ELSE 0 END) as despesas
        FROM movimentacoes 
        WHERE (? = 1 OR grupo_id = ?) AND MONTH(data) = ? AND YEAR(data) = ?");
    $stmt->execute([$is_admin, $grupo_id, $mes_str, $ano_filtro]);
    $res = $stmt->fetch();
    
    $receitas = $res['receitas'] ?? 0;
    $despesas = $res['despesas'] ?? 0;
    $resultado = $receitas - $despesas;
    $saldo_acumulado += $resultado;
    
    $relatorio[] = [
        'mes' => $meses_nomes[$mes_str],
        'receitas' => $receitas,
        'despesas' => $despesas,
        'resultado' => $resultado,
        'acumulado' => $saldo_acumulado
    ];
}

// 2. Lógica de Exportação CSV
if (isset($_GET['action']) && $_GET['action'] === 'export') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=fluxo_de_caixa_' . $ano_filtro . '.csv');
    
    $output = fopen('php://output', 'w');
    // Bom para Excel pt-BR
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    fputcsv($output, ['Mês', 'Receitas', 'Despesas', 'Resultado Mensal', 'Saldo Acumulado'], ';');
    
    foreach ($relatorio as $linha) {
        fputcsv($output, [
            $linha['mes'],
            number_format($linha['receitas'], 2, ',', ''),
            number_format($linha['despesas'], 2, ',', ''),
            number_format($linha['resultado'], 2, ',', ''),
            number_format($linha['acumulado'], 2, ',', '')
        ], ';');
    }
    fclose($output);
    exit;
}
?>

<div class="header-actions" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
    <h3>Relatório Anual de Fluxo de Caixa</h3>
    <a href="index.php?page=fluxo&ano=<?= $ano_filtro ?>&action=export" class="btn btn-primary">
        <i class="fas fa-file-csv"></i> Exportar para CSV
    </a>
</div>

<div class="card">
    <form action="index.php" method="GET" class="form-inline">
        <input type="hidden" name="page" value="fluxo">
        <div class="form-group">
            <label>Ano Base</label>
            <select name="ano" class="form-control">
                <?php for($y=date('Y')-5; $y<=date('Y')+1; $y++): ?>
                    <option value="<?= $y ?>" <?= $ano_filtro == $y ? 'selected' : '' ?>><?= $y ?></option>
                <?php endfor; ?>
            </select>
        </div>
        <div class="form-group">
            <button type="submit" class="btn btn-primary">Atualizar</button>
        </div>
    </form>

    <div class="table-responsive" style="margin-top: 2rem;">
        <table>
            <thead>
                <tr>
                    <th>Mês</th>
                    <th>Receitas</th>
                    <th>Despesas</th>
                    <th>Resultado (Mês)</th>
                    <th>Saldo Acumulado</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($relatorio as $r): ?>
                    <tr>
                        <td style="font-weight: 500;"><?= $r['mes'] ?></td>
                        <td style="color: var(--success);"><?= formatarMoeda($r['receitas']) ?></td>
                        <td style="color: var(--danger);"><?= formatarMoeda($r['despesas']) ?></td>
                        <td style="font-weight: 600; color: <?= $r['resultado'] >= 0 ? 'var(--success)' : 'var(--danger)' ?>;">
                            <?= formatarMoeda($r['resultado']) ?>
                        </td>
                        <td style="font-weight: bold; background: #fdfdfd;"><?= formatarMoeda($r['acumulado']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="alert" style="background: #e3f2fd; color: #0d47a1; margin-top: 1rem; border-left: 5px solid #2196f3;">
    <i class="fas fa-info-circle"></i> O <strong>Saldo Acumulado</strong> considera todas as movimentações desde o início dos registros, incluindo anos anteriores ao selecionado.
</div>
