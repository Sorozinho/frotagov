<?php
require __DIR__ . '/../bootstrap.php';

use App\Database\Conexao;
use App\Exceptions\RegistroVinculadoException;
use App\Model\Automovel;
use App\Model\MaquinaPesada;
use App\Repository\AbastecimentoRepositorio;
use App\Repository\SecretariaRepositorio;
use App\Repository\VeiculoRepositorio;

$pdo              = Conexao::get();
$repo             = new VeiculoRepositorio($pdo);
$secretariaRepo   = new SecretariaRepositorio($pdo);
$abastecimentoRepo = new AbastecimentoRepositorio($pdo, $repo);

$erro = null;
$form = ['id' => '', 'tipo' => 'automovel', 'secretaria_id' => '', 'descricao' => '', 'placa' => '', 'medidor_atual' => '0'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';
    try {
        if ($acao === 'salvar') {
            $form       = $_POST;
            $id         = $_POST['id'] !== '' ? (int) $_POST['id'] : null;
            $secretaria = (int) ($_POST['secretaria_id'] ?? 0);
            $descricao  = $_POST['descricao'] ?? '';
            $medidor    = paraFloat($_POST['medidor_atual'] ?? '0');

            // Cria o objeto da classe certa conforme o tipo escolhido
            $veiculo = ($_POST['tipo'] ?? '') === 'maquina'
                ? new MaquinaPesada($secretaria, $descricao, $medidor, $id)
                : new Automovel($secretaria, $descricao, $medidor, $_POST['placa'] ?? '', $id);

            $repo->salvar($veiculo);
            flash('sucesso', 'Veículo salvo com sucesso.');
            redirecionar('veiculos.php');
        }
        if ($acao === 'excluir') {
            $repo->excluir((int) $_POST['id']);
            flash('sucesso', 'Veículo excluído.');
            redirecionar('veiculos.php');
        }
    } catch (InvalidArgumentException | RegistroVinculadoException $e) {
        $erro = $e->getMessage();
    } catch (PDOException $e) {
        $erro = $e->getCode() === '23000' ? 'Já existe um veículo com essa placa.' : 'Erro no banco de dados.';
    }
}

if (isset($_GET['editar']) && $erro === null) {
    $v = $repo->buscarPorId((int) $_GET['editar']);
    if ($v) {
        $form = [
            'id'            => $v->getId(),
            'tipo'          => $v->getTipo(),
            'secretaria_id' => $v->getSecretariaId(),
            'descricao'     => $v->getDescricao(),
            'placa'         => $v->getPlaca() ?? '',
            'medidor_atual' => $v->getMedidorAtual(),
        ];
    }
}

$secretarias = $secretariaRepo->listarTodos();
$siglas = [];
foreach ($secretarias as $s) {
    $siglas[$s->getId()] = $s->getSigla();
}
$veiculos = $repo->listarTodos();

$titulo = 'Veículos';
require __DIR__ . '/../templates/topo.php';
?>
<div class="cartao">
    <h2><?= $form['id'] ? 'Editar veículo' : 'Novo veículo' ?></h2>
    <form method="post">
        <input type="hidden" name="acao" value="salvar">
        <input type="hidden" name="id" value="<?= e($form['id']) ?>">
        <div class="linha">
            <div>
                <label>Tipo</label>
                <select name="tipo" id="tipo" onchange="trocarTipo()">
                    <option value="automovel" <?= $form['tipo'] === 'automovel' ? 'selected' : '' ?>>Automóvel (km)</option>
                    <option value="maquina" <?= $form['tipo'] === 'maquina' ? 'selected' : '' ?>>Máquina pesada (horas)</option>
                </select>
            </div>
            <div>
                <label>Secretaria</label>
                <select name="secretaria_id" required>
                    <option value="">Selecione...</option>
                    <?php foreach ($secretarias as $s): ?>
                        <option value="<?= $s->getId() ?>" <?= (string) $form['secretaria_id'] === (string) $s->getId() ? 'selected' : '' ?>>
                            <?= e($s->getSigla() . ' - ' . $s->getNome()) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div><label>Descrição</label><input name="descricao" value="<?= e($form['descricao']) ?>" placeholder="Ex.: Fiat Strada 2022" required></div>
            <div id="campoPlaca"><label>Placa</label><input name="placa" value="<?= e($form['placa']) ?>" placeholder="ABC1D23"></div>
            <div><label id="rotuloMedidor">Hodômetro (km)</label><input name="medidor_atual" value="<?= e($form['medidor_atual']) ?>"></div>
        </div>
        <button type="submit">Salvar</button>
        <?php if ($form['id']): ?><a class="botao" href="veiculos.php" style="background:#7b8794">Cancelar</a><?php endif; ?>
    </form>
</div>

<table>
    <tr>
        <th>Descrição</th><th>Tipo</th><th>Placa</th><th>Secretaria</th>
        <th class="num">Medidor</th><th class="num">Consumo médio</th><th class="num">Próx. revisão</th><th></th>
    </tr>
    <?php foreach ($veiculos as $v):
        $consumo = $v->consumoMedio($abastecimentoRepo->listarPorVeiculo($v->getId())); // polimorfismo
    ?>
        <tr>
            <td><?= e($v->getDescricao()) ?></td>
            <td><?= e($v->getTipoDescricao()) ?></td>
            <td><?= e($v->getPlaca() ?? '—') ?></td>
            <td><?= e($siglas[$v->getSecretariaId()] ?? '') ?></td>
            <td class="num"><?= num($v->getMedidorAtual(), 1) ?> <?= $v->getUnidadeMedidor() ?></td>
            <td class="num"><?= $consumo === null ? '—' : num($consumo) . ' ' . $v->getUnidadeConsumo() ?></td>
            <td class="num">
                <?= num($v->proximaRevisao(), 0) ?> <?= $v->getUnidadeMedidor() ?>
                <?php if ($v->revisaoProxima()): ?><br><span class="selo">revisão próxima</span><?php endif; ?>
            </td>
            <td>
                <a class="botao editar" href="?editar=<?= $v->getId() ?>">Editar</a>
                <form method="post" class="inline" onsubmit="return confirm('Excluir este veículo?')">
                    <input type="hidden" name="acao" value="excluir">
                    <input type="hidden" name="id" value="<?= $v->getId() ?>">
                    <button class="perigo">Excluir</button>
                </form>
            </td>
        </tr>
    <?php endforeach; ?>
</table>

<script>
// Esconde a placa e troca o rótulo quando o tipo é "máquina"
function trocarTipo() {
    const maquina = document.getElementById('tipo').value === 'maquina';
    document.getElementById('campoPlaca').style.display = maquina ? 'none' : '';
    document.getElementById('rotuloMedidor').textContent = maquina ? 'Horímetro (horas)' : 'Hodômetro (km)';
}
trocarTipo();
</script>
<?php require __DIR__ . '/../templates/rodape.php'; ?>
