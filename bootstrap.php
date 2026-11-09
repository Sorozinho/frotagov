<?php
/**
 * Arquivo carregado no início de todas as páginas.
 * - Registra o autoload (carrega as classes automaticamente, sem um require para cada uma)
 * - Inicia a sessão (usada para mensagens de sucesso/erro)
 * - Define funções auxiliares pequenas
 */

declare(strict_types=1);

// Autoload: a classe App\Model\Veiculo fica em src/Model/Veiculo.php
spl_autoload_register(function (string $classe): void {
    $prefixo = 'App\\';
    if (!str_starts_with($classe, $prefixo)) {
        return;
    }
    $relativo = substr($classe, strlen($prefixo));
    $arquivo  = __DIR__ . '/src/' . str_replace('\\', '/', $relativo) . '.php';
    if (is_file($arquivo)) {
        require $arquivo;
    }
});

session_start();

/** Escapa texto antes de mostrar no HTML (evita XSS). */
function e(mixed $valor): string
{
    return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
}

/** Formata número no padrão brasileiro. */
function num(float $valor, int $casas = 2): string
{
    return number_format($valor, $casas, ',', '.');
}

/** Formata data Y-m-d como d/m/Y. */
function dataBr(string $data): string
{
    return date('d/m/Y', strtotime($data));
}

/** Guarda uma mensagem para mostrar na próxima página. */
function flash(string $tipo, string $mensagem): void
{
    $_SESSION['flash'] = ['tipo' => $tipo, 'mensagem' => $mensagem];
}

/** Redireciona e encerra o script. */
function redirecionar(string $url): never
{
    header('Location: ' . $url);
    exit;
}

/** Converte "1.234,56" ou "1234.56" em float. */
function paraFloat(string $valor): float
{
    $valor = trim($valor);
    if (str_contains($valor, ',')) {
        $valor = str_replace(['.', ','], ['', '.'], $valor);
    }
    return (float) $valor;
}
