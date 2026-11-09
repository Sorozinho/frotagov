<?php
/** @var string $titulo */
$paginaAtual = basename($_SERVER['SCRIPT_NAME']);
$menu = [
    'index.php'          => 'Início',
    'secretarias.php'    => 'Secretarias',
    'veiculos.php'       => 'Veículos',
    'abastecimentos.php' => 'Abastecimentos',
    'ordens.php'         => 'Manutenções',
    'relatorio.php'      => 'Relatório',
];
?>
<!doctype html>
<html lang="pt-br">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($titulo) ?> | FrotaGov</title>
    <link rel="stylesheet" href="css/estilo.css">
</head>
<body>
<header>
    <strong class="logo">FrotaGov</strong>
    <nav>
        <?php foreach ($menu as $arquivo => $nome): ?>
            <a href="<?= $arquivo ?>" class="<?= $arquivo === $paginaAtual ? 'ativo' : '' ?>"><?= $nome ?></a>
        <?php endforeach; ?>
    </nav>
</header>
<main>
<h1><?= e($titulo) ?></h1>
<?php if (!empty($_SESSION['flash'])): ?>
    <div class="alerta <?= e($_SESSION['flash']['tipo']) ?>"><?= e($_SESSION['flash']['mensagem']) ?></div>
    <?php unset($_SESSION['flash']); ?>
<?php endif; ?>
<?php if (!empty($erro)): ?>
    <div class="alerta erro"><?= e($erro) ?></div>
<?php endif; ?>
