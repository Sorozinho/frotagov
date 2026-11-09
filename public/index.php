<?php
require __DIR__ . '/../bootstrap.php';

use App\Database\Conexao;
use App\Repository\SecretariaRepositorio;
use App\Repository\VeiculoRepositorio;

try {
    $pdo = Conexao::get();
} catch (Throwable $e) {
    // Mensagem amigável se o banco não estiver configurado
    $titulo = 'Erro de conexão';
    $erro = 'Não foi possível conectar ao banco: ' . $e->getMessage();
    require __DIR__ . '/../templates/topo.php';
    echo '<p>Confira o arquivo <code>config/config.php</code> e se o MySQL está ligado no XAMPP.</p>';
    require __DIR__ . '/../templates/rodape.php';
    exit;
}

$veiculos    = (new VeiculoRepositorio($pdo))->listarTodos();
$secretarias = (new SecretariaRepositorio($pdo))->listarTodos();

$mes = $pdo->query(
    "SELECT COALESCE(SUM(valor_total),0) AS total, COALESCE(SUM(litros),0) AS litros
       FROM abastecimento
      WHERE data >= DATE_FORMAT(CURDATE(), '%Y-%m-01')"
)->fetch();

// Filtra os que estão perto da revisão (polimorfismo: cada tipo tem seu intervalo)
$alertas = [];
foreach ($veiculos as $v) {
    if ($v->revisaoProxima()) {
        $alertas[] = $v;
    }
}

$titulo = 'Painel da frota';
require __DIR__ . '/../templates/topo.php';
?>
<div class="grade">
    <div class="cartao"><div>Secretarias</div><div class="numero"><?= count($secretarias) ?></div></div>
    <div class="cartao"><div>Veículos e máquinas</div><div class="numero"><?= count($veiculos) ?></div></div>
    <div class="cartao"><div>Combustível no mês</div><div class="numero">R$ <?= num((float) $mes['total']) ?></div></div>
    <div class="cartao"><div>Litros no mês</div><div class="numero"><?= num((float) $mes['litros'], 0) ?></div></div>
</div>

<div class="cartao">
    <h2>Revisões próximas</h2>
    <?php if (!$alertas): ?>
        <p>Nenhum veículo perto da revisão.</p>
    <?php else: ?>
        <table>
            <tr><th>Veículo</th><th>Tipo</th><th class="num">Atual</th><th class="num">Revisão em</th></tr>
            <?php foreach ($alertas as $v): ?>
                <tr>
                    <td><?= e($v->getDescricao()) ?></td>
                    <td><?= e($v->getTipoDescricao()) ?></td>
                    <td class="num"><?= num($v->getMedidorAtual(), 1) . ' ' . $v->getUnidadeMedidor() ?></td>
                    <td class="num"><?= num($v->proximaRevisao(), 0) . ' ' . $v->getUnidadeMedidor() ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php endif; ?>
</div>
<?php require __DIR__ . '/../templates/rodape.php'; ?>
