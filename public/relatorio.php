<?php
require __DIR__ . '/../bootstrap.php';

use App\Database\Conexao;
use App\Repository\RelatorioRepositorio;

$inicio = $_GET['inicio'] ?? date('Y-01-01');
$fim    = $_GET['fim'] ?? date('Y-m-d');

// valida as datas antes de mandar para o banco
$erro = null;
foreach ([$inicio, $fim] as $d) {
    $obj = DateTimeImmutable::createFromFormat('Y-m-d', $d);
    if (!$obj || $obj->format('Y-m-d') !== $d) {
        $erro = 'Período inválido.';
        $inicio = date('Y-m-01');
        $fim = date('Y-m-d');
        break;
    }
}

$relatorio      = new RelatorioRepositorio(Conexao::get());
$porSecretaria  = $relatorio->custoPorSecretaria($inicio, $fim);
$porVeiculo     = $relatorio->custoPorVeiculo($inicio, $fim);

$titulo = 'Relatório de custos';
require __DIR__ . '/../templates/topo.php';
?>
<div class="cartao">
    <form method="get" class="linha">
        <div><label>De</label><input type="date" name="inicio" value="<?= e($inicio) ?>"></div>
        <div><label>Até</label><input type="date" name="fim" value="<?= e($fim) ?>"></div>
        <div><button type="submit" style="margin-top:34px">Filtrar</button></div>
    </form>
</div>

<h2>Por secretaria</h2>
<table>
    <tr><th>Secretaria</th><th class="num">Combustível</th><th class="num">Manutenção</th><th class="num">Total</th></tr>
    <?php $tc = $tm = 0; foreach ($porSecretaria as $l): $tc += $l['combustivel']; $tm += $l['manutencao']; ?>
        <tr>
            <td><?= e($l['sigla'] . ' - ' . $l['secretaria']) ?></td>
            <td class="num">R$ <?= num((float) $l['combustivel']) ?></td>
            <td class="num">R$ <?= num((float) $l['manutencao']) ?></td>
            <td class="num">R$ <?= num((float) $l['combustivel'] + (float) $l['manutencao']) ?></td>
        </tr>
    <?php endforeach; ?>
    <tr><th>Total geral</th><th class="num">R$ <?= num($tc) ?></th><th class="num">R$ <?= num($tm) ?></th><th class="num">R$ <?= num($tc + $tm) ?></th></tr>
</table>

<h2>Por veículo</h2>
<table>
    <tr><th>Sec.</th><th>Veículo</th><th>Placa</th><th class="num">Litros</th><th class="num">Combustível</th><th class="num">Manutenção</th><th class="num">Total</th></tr>
    <?php foreach ($porVeiculo as $l): ?>
        <tr>
            <td><?= e($l['sigla']) ?></td>
            <td><?= e($l['descricao']) ?></td>
            <td><?= e($l['placa'] ?? '—') ?></td>
            <td class="num"><?= num((float) $l['litros']) ?></td>
            <td class="num">R$ <?= num((float) $l['combustivel']) ?></td>
            <td class="num">R$ <?= num((float) $l['manutencao']) ?></td>
            <td class="num">R$ <?= num((float) $l['combustivel'] + (float) $l['manutencao']) ?></td>
        </tr>
    <?php endforeach; ?>
</table>
<?php require __DIR__ . '/../templates/rodape.php'; ?>
