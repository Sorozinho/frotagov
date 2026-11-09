<?php
declare(strict_types=1);

namespace App\Database;

use PDO;
use RuntimeException;

/**
 * Cria UMA conexão PDO e reaproveita ela no sistema todo.
 * (padrão Singleton: o construtor é privado e só existe uma instância)
 */
final class Conexao
{
    private static ?PDO $pdo = null;

    private function __construct()
    {
    }

    public static function get(): PDO
    {
        if (self::$pdo === null) {
            $arquivo = __DIR__ . '/../../config/config.php';
            if (!is_file($arquivo)) {
                throw new RuntimeException(
                    'Arquivo config/config.php não encontrado. Copie config.example.php para config.php.'
                );
            }
            $c = require $arquivo;

            $dsn = "mysql:host={$c['host']};port={$c['porta']};dbname={$c['banco']};charset=utf8mb4";

            self::$pdo = new PDO($dsn, $c['usuario'], $c['senha'], [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // erro vira exceção
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // linhas como array associativo
                PDO::ATTR_EMULATE_PREPARES   => false,                  // prepared statements reais
            ]);
        }
        return self::$pdo;
    }
}
