<?php
require __DIR__ . '/../bootstrap.php';

use App\Database\Conexao;
use App\Model\Abastecimento;
use App\Repository\AbastecimentoRepositorio;
use App\Repository\VeiculoRepositorio;

$pdo         = Conexao::get();
$veiculoRepo = new VeiculoRepositorio($pdo);
$repo        = new AbastecimentoRepositorio($pdo, $veiculoRepo);

$erro = null;
$form = ['id' => '', 'veiculo_id' => '', 'data' => date('Y-m-d'), 'litros' => '', 'valor_total' => '', 'medidor' => '', 'posto' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';
    try {
        if ($acao === 'salvar') {
            $form    = $_POST;
            $veiculo = $veiculoRepo->buscarPorId((int) ($_POST['veiculo_id'] ?? 0));
            if ($veiculo === null) {
                throw new InvalidArgumentException('Selecione um veículo.');
            }
            $abastecimento = new Abastecimento(
                $veiculo,
                $_POST['data'] ?? '',
                paraFloat($_POST['litros'] ?? '0'),
                paraFloat($_POST['valor_total'] ?? '0'),
                paraFloat($_POST['medidor'] ?? '0'),
                $_POST['posto'] ?? '',
                $_POST['id'] !== '' ? (int) $_POST['id'] : null
            );
            $repo->salvar($abastecimento); // grava + atualiza hodômetro numa transação
            flash('sucesso', 'Abastecimento salvo com sucesso.');
            redirecionar('abastecimentos.php');
        }
        if ($acao === 'excluir') {
            $repo->excluir((int) $_POST['id']);
            flash('sucesso', 'Abastecimento excluído.');
            redirecionar('abastecimentos.php');
        }
    } catch (InvalidArgumentException $e) {
        $erro = $e->getMessage();
    } catch (PDOException $e) {
        $erro = 'Erro no banco de dados.';
    }
}

if (isset($_GET['editar']) && $erro === null) {
    $a = $repo->buscarPorId((int) $_GET['editar']);
    if ($a) {
        $form = [
            'id'          => $a->getId(),
            'veiculo_id'  => $a->getVeiculo()->getId(),
            'data'        => $a->getData(),
            'litros'      => $a->getLitros(),
            'valor_total' => $a->getValorTotal(),
            'medidor'     => $a->getMedidor(),
            'posto'       => $a->getPosto(),
        ];
    }
}

$veiculos       = $veiculoRepo->listarTodos();
$abastecimentos = $repo->listarTodos();

$titulo = 'Abastecimentos';
require __DIR__ . '/../templates/topo.php';
?>
<div class="cartao">
    <h2><?= $form['id'] ? 'Editar abastecimento' : 'Novo abastecimento' ?></h2>
    <form method="post">
        <input type="hidden" name="acao" value="salvar">
        <input type="hidden" name="id" value="<?= e($form['id']) ?>">
        <div class="linha">
            <div>
                <label>Veículo</label>
                <select name="veiculo_id" required>
                    <option value="">Selecione...</option>
                    <?php foreach ($veiculos as $v): ?>
                        <option value="<?= $v->getId() ?>" <?= (string) $form['veiculo_id'] === (string) $v->getId() ? 'selected' : '' ?>>
                            <?= e($v->getDescricao() . ($v->getPlaca() ? ' - ' . $v->getPlaca() : '')) ?>
                            (<?= num($v->getMedidorAtual(), 1) . ' ' . $v->getUnidadeMedidor() ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div><label>Data</label><input type="date" name="data" value="<?= e($form['data']) ?>" required></div>
            <div><label>Litros</label><input name="litros" value="<?= e($form['litros']) ?>" required></div>
            <div><label>Valor total (R$)</label><input name="valor_total" value="<?= e($form['valor_total']) ?>" required></div>
            <div><label>Km / horas no momento</label><input name="medidor" value="<?= e($form['medidor']) ?>" required></div>
            <div><label>Posto</label><input name="posto" value="<?= e($form['posto']) ?>" required></div>
        </div>
        <button type="submit">Salvar</button>
        <?php if ($form['id']): ?><a class="botao" href="abastecimentos.php" style="background:#7b8794">Cancelar</a><?php endif; ?>
    </form>
</div>

<table>
    <tr>
        <th>Data</th><th>Veículo</th><th class="num">Litros</th><th class="num">Valor</th>
        <th class="num">R$/L</th><th class="num">Medidor</th><th>Posto</th><th></th>
    </tr>
    <?php foreach ($abastecimentos as $a): $v = $a->getVeiculo(); ?>
        <tr>
            <td><?= dataBr($a->getData()) ?></td>
            <td><?= e($v->getDescricao()) ?></td>
            <td class="num"><?= num($a->getLitros()) ?></td>
            <td class="num">R$ <?= num($a->getValorTotal()) ?></td>
            <td class="num"><?= num($a->precoPorLitro(), 3) ?></td>
            <td class="num"><?= num($a->getMedidor(), 1) . ' ' . $v->getUnidadeMedidor() ?></td>
            <td><?= e($a->getPosto()) ?></td>
            <td>
                <a class="botao editar" href="?editar=<?= $a->getId() ?>">Editar</a>
                <form method="post" class="inline" onsubmit="return confirm('Excluir este abastecimento?')">
                    <input type="hidden" name="acao" value="excluir">
                    <input type="hidden" name="id" value="<?= $a->getId() ?>">
                    <button class="perigo">Excluir</button>
                </form>
            </td>
        </tr>
    <?php endforeach; ?>
</table>
<?php require __DIR__ . '/../templates/rodape.php'; ?>
