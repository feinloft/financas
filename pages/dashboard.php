<?php
/**
 * Dashboard (Página Inicial)
 * Sistema de Gestão Financeira (SGF)
 */

$mes_atual = date('m');
$ano_atual = date('Y');

$grupo_id = getGrupoId();
$is_admin = ehAdmin();

// 1. Totais do mês atual
$stmt = $pdo->prepare("SELECT 
    SUM(CASE WHEN tipo = 'receita' THEN valor ELSE 0 END) as total_receita,
    SUM(CASE WHEN tipo = 'despesa' THEN valor ELSE 0 END) as total_despesa
    FROM movimentacoes 
    WHERE MONTH(data) = ? AND YEAR(data) = ? AND (? = 1 OR grupo_id = ?)");
$stmt->execute([$mes_atual, $ano_atual, $is_admin, $grupo_id]);
$totais = $stmt->fetch();

$total_receita = $totais['total_receita'] ?? 0;
$total_despesa = $totais['total_despesa'] ?? 0;
$saldo_liquido = $total_receita - $total_despesa;

// 2. Dados para o gráfico (últimos 6 meses)
$grafico_labels = [];
$grafico_receitas = [];
$grafico_despesas = [];

for ($i = 5; $i >= 0; $i--) {
    $data_ponto = date('Y-m-01', strtotime("-$i months"));
    $mes_ponto = date('m', strtotime($data_ponto));
    $ano_ponto = date('Y', strtotime($data_ponto));
    $label_ponto = date('M/y', strtotime($data_ponto)); // Ex: Jan/23

    $stmt = $pdo->prepare("SELECT 
        SUM(CASE WHEN tipo = 'receita' THEN valor ELSE 0 END) as r,
        SUM(CASE WHEN tipo = 'despesa' THEN valor ELSE 0 END) as d
        FROM movimentacoes 
        WHERE MONTH(data) = ? AND YEAR(data) = ? AND (? = 1 OR grupo_id = ?)");
    $stmt->execute([$mes_ponto, $ano_ponto, $is_admin, $grupo_id]);
    $res = $stmt->fetch();

    $grafico_labels[] = $label_ponto;
    $grafico_receitas[] = $res['r'] ?? 0;
    $grafico_despesas[] = $res['d'] ?? 0;
}

// 3. Dados para o gráfico de pizza (despesas por categoria no mês atual)
$stmt = $pdo->prepare("SELECT 
    c.nome, 
    c.cor,
    SUM(m.valor) as total
    FROM movimentacoes m
    JOIN categorias c ON m.categoria_id = c.id
    WHERE m.tipo = 'despesa' AND MONTH(m.data) = ? AND YEAR(m.data) = ? AND (? = 1 OR m.grupo_id = ?)
    GROUP BY c.id
    ORDER BY total DESC
    LIMIT 5");
$stmt->execute([$mes_atual, $ano_atual, $is_admin, $grupo_id]);
$top_categorias = $stmt->fetchAll();

$cat_labels = [];
$cat_valores = [];
$cat_cores = [];

foreach ($top_categorias as $cat) {
    $cat_labels[] = $cat['nome'];
    $cat_valores[] = $cat['total'];
    $cat_cores[] = $cat['cor'];
}
?>

<div class="dashboard">
    <div class="summary-grid">
        <div class="summary-card income">
            <h3>Receitas (Este Mês)</h3>
            <div class="value"><?= formatarMoeda($total_receita) ?></div>
        </div>
        <div class="summary-card expense">
            <h3>Despesas (Este Mês)</h3>
            <div class="value"><?= formatarMoeda($total_despesa) ?></div>
        </div>
        <div class="summary-card balance">
            <h3>Saldo Líquido</h3>
            <div class="value" style="color: <?= $saldo_liquido >= 0 ? 'var(--success)' : 'var(--danger)' ?>">
                <?= formatarMoeda($saldo_liquido) ?>
            </div>
        </div>
    </div>

    <div class="dashboard-charts">
        <div class="card">
            <h3>Fluxo de Caixa (Últimos 6 Meses)</h3>
            <div style="height: 300px; margin-top: 20px;">
                <canvas id="cashFlowChart"></canvas>
            </div>
        </div>

        <div class="card">
            <h3>Despesas por Categoria (Este Mês)</h3>
            <div style="height: 300px; margin-top: 20px;">
                <canvas id="categoryChart"></canvas>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('cashFlowChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?= json_encode($grafico_labels) ?>,
            datasets: [{
                label: 'Receitas',
                data: <?= json_encode($grafico_receitas) ?>,
                borderColor: '#2ecc71',
                backgroundColor: 'rgba(46, 204, 113, 0.1)',
                fill: true,
                tension: 0.3
            }, {
                label: 'Despesas',
                data: <?= json_encode($grafico_despesas) ?>,
                borderColor: '#e74c3c',
                backgroundColor: 'rgba(231, 76, 60, 0.1)',
                fill: true,
                tension: 0.3
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return 'R$ ' + value.toLocaleString('pt-BR');
                        }
                    }
                }
            },
            plugins: {
                legend: {
                    position: 'top',
                }
            }
        }
    });

    // Gráfico de Categorias
    const ctxCat = document.getElementById('categoryChart').getContext('2d');
    new Chart(ctxCat, {
        type: 'doughnut',
        data: {
            labels: <?= json_encode($cat_labels) ?>,
            datasets: [{
                data: <?= json_encode($cat_valores) ?>,
                backgroundColor: <?= json_encode($cat_cores) ?>,
                borderWidth: 2,
                hoverOffset: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        padding: 20,
                        usePointStyle: true
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const label = context.label || '';
                            const value = context.raw || 0;
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percent = ((value / total) * 100).toFixed(1);
                            return label + ': R$ ' + value.toLocaleString('pt-BR') + ' (' + percent + '%)';
                        }
                    }
                }
            },
            cutout: '70%'
        }
    });
});
</script>
