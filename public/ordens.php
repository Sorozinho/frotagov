<?php
require __DIR__ . '/../bootstrap.php';

use App\Database\Conexao;
use App\Model\OrdemManutencao;
use App\Repository\OrdemManutencaoRepositorio;
use App\Repository\VeiculoRepositorio;

const LINHAS_ITENS = 6; // quantas linhas de item aparecem no formulário

$pdo         = Conexao::get();
$veiculoRepo = new VeiculoRepositorio($pdo);
$repo        = new OrdemManutencaoRepositorio($pdo, $veiculoRepo);

$erro = null;
$form = ['id' => '', 'veiculo_id' => '', 'data' => date('Y-m-d'), 'oficina' => '', 'descricao' => '', 'itens' => []];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';
    try {
        if ($acao === 'salvar') {
            $form    = $_POST;
            $veiculo = $veiculoRepo->buscarPorId((int) ($_POST['veiculo_id'] ?? 0));
            if ($veiculo === null) {
                throw new InvalidArgumentException('Selecione um veículo.');
            }
            $ordem = new OrdemManutencao(
                $veiculo,
                $_POST['data'] ?? '',
                $_POST['oficina'] ?? '',
                $_POST['descricao'] ?? '',
                $_POST['id'] !== '' ? (int) $_POST['id'] : null
            );
            // Composição: os itens entram pela própria ordem
            foreach ($_POST['itens'] ?? [] as $item) {
                if (trim($item['descricao'] ?? '') === '') {
                    continue; // linha em branco, ignora
                }
                $ordem->adicionarItem(
                    $item['tipo'],
                    $item['descricao'],
                    paraFloat($item['quantidade'] ?: '1'),
                    paraFloat($item['valor_unitario'] ?: '0')
                );
            }
            $repo->salvar($ordem); // ordem + itens numa transação
            flash('sucesso', 'Ordem de manutenção salva com sucesso.');
            redirecionar('ordens.php');
        }
        if ($acao === 'excluir') {
            $repo->excluir((int) $_POST['id']); // itens somem junto (ON DELETE CASCADE)
            flash('sucesso', 'Ordem excluída (com seus itens).');
            redirecionar('ordens.php');
        }
    } catch (InvalidArgumentException $e) {
        $erro = $e->getMessage();
    } catch (PDOException $e) {
        $erro = 'Erro no banco de dados.';
    }
}

if (isset($_GET['editar']) && $erro === null) {
    $o = $repo->buscarPorId((int) $_GET['editar']);
    if ($o) {
        $itens = [];
        foreach ($o->getItens() as $i) {
            $itens[] = [
                'tipo'           => $i->getTipo(),
                'descricao'      => $i->getDescricao(),
                'quantidade'     => $i->getQuantidade(),
                'valor_unitario' => $i->getValorUnitario(),
            ];
        }
        $form = [
            'id'         => $o->getId(),
            'veiculo_id' => $o->getVeiculo()->getId(),
            'data'       => $o->getData(),
            'oficina'    => $o->getOficina(),
            'descricao'  => $o->getDescricao(),
            'itens'      => $itens,
        ];
    }
}

$veiculos = $veiculoRepo->listarTodos();
$ordens   = $repo->listarTodos();

$titulo = 'Manutenções';
require __DIR__ . '/../templates/topo.php';
?>
<div class="cartao">
    <h2><?= $form['id'] ? 'Editar ordem de manutenção' : 'Nova ordem de manutenção' ?></h2>
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
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div><label>Data</label><input type="date" name="data" value="<?= e($form['data']) ?>" required></div>
            <div><label>Oficina</label><input name="oficina" value="<?= e($form['oficina']) ?>" required></div>
            <div><label>Descrição</label><input name="descricao" value="<?= e($form['descricao']) ?>" placeholder="Ex.: Revisão 50.000 km" required></div>
        </div>

        <label>Itens (peças e serviços) — deixe em branco as linhas que não usar</label>
        <table class="itens">
            <tr><th>Tipo</th><th>Descrição</th><th class="num">Qtd</th><th class="num">Valor unit. (R$)</th></tr>
            <?php for ($i = 0; $i < LINHAS_ITENS; $i++):
                $it = $form['itens'][$i] ?? ['tipo' => 'peca', 'descricao' => '', 'quantidade' => '', 'valor_unitario' => ''];
            ?>
                <tr>
                    <td>
                        <select name="itens[<?= $i ?>][tipo]">
                            <option value="peca" <?= $it['tipo'] === 'peca' ? 'selected' : '' ?>>Peça</option>
                            <option value="servico" <?= $it['tipo'] === 'servico' ? 'selected' : '' ?>>Serviço</option>
                        </select>
                    </td>
                    <td><input name="itens[<?= $i ?>][descricao]" value="<?= e($it['descricao']) ?>"></td>
                    <td><input name="itens[<?= $i ?>][quantidade]" value="<?= e($it['quantidade']) ?>"></td>
                    <td><input name="itens[<?= $i ?>][valor_unitario]" value="<?= e($it['valor_unitario']) ?>"></td>
                </tr>
            <?php endfor; ?>
        </table>
        <button type="submit">Salvar</button>
        <?php if ($form['id']): ?><a class="botao" href="ordens.php" style="background:#7b8794">Cancelar</a><?php endif; ?>
    </form>
</div>

<?php foreach ($ordens as $o): $v = $o->getVeiculo(); ?>
    <div class="cartao">
        <strong>#<?= $o->getId() ?> · <?= dataBr($o->getData()) ?> · <?= e($v->getDescricao()) ?></strong>
        — <?= e($o->getDescricao()) ?> (<?= e($o->getOficina()) ?>)
        <table style="margin-top:8px">
            <tr><th>Tipo</th><th>Descrição</th><th class="num">Qtd</th><th class="num">Unit.</th><th class="num">Subtotal</th></tr>
            <?php foreach ($o->getItens() as $item): ?>
                <tr>
                    <td><?= $item->getTipoDescricao() ?></td>
                    <td><?= e($item->getDescricao()) ?></td>
                    <td class="num"><?= num($item->getQuantidade()) ?></td>
                    <td class="num">R$ <?= num($item->getValorUnitario()) ?></td>
                    <td class="num">R$ <?= num($item->subtotal()) ?></td>
                </tr>
            <?php endforeach; ?>
            <tr><th colspan="4">Total</th><th class="num">R$ <?= num($o->valorTotal()) ?></th></tr>
        </table>
        <a class="botao editar" href="?editar=<?= $o->getId() ?>">Editar</a>
        <form method="post" class="inline" onsubmit="return confirm('Excluir esta ordem e todos os itens?')">
            <input type="hidden" name="acao" value="excluir">
            <input type="hidden" name="id" value="<?= $o->getId() ?>">
            <button class="perigo">Excluir</button>
        </form>
    </div>
<?php endforeach; ?>
<?php require __DIR__ . '/../templates/rodape.php'; ?>
