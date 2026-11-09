<?php
require __DIR__ . '/../bootstrap.php';

use App\Database\Conexao;
use App\Exceptions\RegistroVinculadoException;
use App\Model\Secretaria;
use App\Repository\SecretariaRepositorio;
use App\Repository\VeiculoRepositorio;

$pdo  = Conexao::get();
$repo = new SecretariaRepositorio($pdo);
$erro = null;
$form = ['id' => '', 'nome' => '', 'sigla' => ''];

// ---------- Ações (POST) ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';
    try {
        if ($acao === 'salvar') {
            $form = $_POST;
            $id = $_POST['id'] !== '' ? (int) $_POST['id'] : null;
            $secretaria = new Secretaria($_POST['nome'] ?? '', $_POST['sigla'] ?? '', $id);
            $repo->salvar($secretaria);
            flash('sucesso', 'Secretaria salva com sucesso.');
            redirecionar('secretarias.php');
        }
        if ($acao === 'excluir') {
            $repo->excluir((int) $_POST['id']);
            flash('sucesso', 'Secretaria excluída.');
            redirecionar('secretarias.php');
        }
    } catch (InvalidArgumentException | RegistroVinculadoException $e) {
        $erro = $e->getMessage();
    } catch (PDOException $e) {
        $erro = $e->getCode() === '23000' ? 'Já existe uma secretaria com essa sigla.' : 'Erro no banco de dados.';
    }
}

// ---------- Carregar para edição (GET ?editar=id) ----------
if (isset($_GET['editar']) && $erro === null) {
    $s = $repo->buscarPorId((int) $_GET['editar']);
    if ($s) {
        $form = ['id' => $s->getId(), 'nome' => $s->getNome(), 'sigla' => $s->getSigla()];
    }
}

$veiculoRepo  = new VeiculoRepositorio($pdo);
// Carrega cada secretaria já com a lista de veículos dela (agregação)
$secretarias  = [];
foreach ($repo->listarTodos() as $s) {
    $secretarias[] = $repo->buscarComVeiculos($s->getId(), $veiculoRepo);
}

$titulo = 'Secretarias';
require __DIR__ . '/../templates/topo.php';
?>
<div class="cartao">
    <h2><?= $form['id'] ? 'Editar secretaria' : 'Nova secretaria' ?></h2>
    <form method="post">
        <input type="hidden" name="acao" value="salvar">
        <input type="hidden" name="id" value="<?= e($form['id']) ?>">
        <div class="linha">
            <div><label>Nome</label><input name="nome" value="<?= e($form['nome']) ?>" required></div>
            <div><label>Sigla</label><input name="sigla" value="<?= e($form['sigla']) ?>" maxlength="10" required></div>
        </div>
        <button type="submit">Salvar</button>
        <?php if ($form['id']): ?><a class="botao" href="secretarias.php" style="background:#7b8794">Cancelar</a><?php endif; ?>
    </form>
</div>

<table>
    <tr><th>Sigla</th><th>Nome</th><th class="num">Veículos</th><th></th></tr>
    <?php foreach ($secretarias as $s): ?>
        <tr>
            <td><?= e($s->getSigla()) ?></td>
            <td><?= e($s->getNome()) ?></td>
            <td class="num"><?= $s->quantidadeVeiculos() ?></td>
            <td>
                <a class="botao editar" href="?editar=<?= $s->getId() ?>">Editar</a>
                <form method="post" class="inline" onsubmit="return confirm('Excluir esta secretaria?')">
                    <input type="hidden" name="acao" value="excluir">
                    <input type="hidden" name="id" value="<?= $s->getId() ?>">
                    <button class="perigo">Excluir</button>
                </form>
            </td>
        </tr>
    <?php endforeach; ?>
</table>
<?php require __DIR__ . '/../templates/rodape.php'; ?>
