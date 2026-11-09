<?php
declare(strict_types=1);

namespace App\Repository;

use App\Contracts\Repositorio;
use App\Exceptions\RegistroVinculadoException;
use InvalidArgumentException;
use PDO;
use PDOException;

/**
 * CLASSE ABSTRATA base de todos os repositórios.
 * Implementa a interface Repositorio só em parte: o que é IGUAL para todos
 * (guardar o PDO, excluir por id, checar o tipo do objeto) fica aqui;
 * o que é DIFERENTE (salvar, buscar, montar o objeto) fica nas filhas.
 * Isso é REUSABILIDADE: o excluir() foi escrito uma vez e vale para todas.
 */
abstract class RepositorioPdo implements Repositorio
{
    public function __construct(protected PDO $pdo)
    {
    }

    /** Nome da tabela no banco. Cada filha informa a sua. */
    abstract protected function tabela(): string;

    /** Classe que esse repositório aceita em salvar(). */
    abstract protected function classeEntidade(): string;

    public function excluir(int $id): void
    {
        try {
            // Prepared statement: o :id é enviado separado do SQL (protege contra SQL Injection)
            $stmt = $this->pdo->prepare("DELETE FROM {$this->tabela()} WHERE id = :id");
            $stmt->execute([':id' => $id]);
        } catch (PDOException $e) {
            // 23000 = violação de integridade (chave estrangeira)
            if ($e->getCode() === '23000') {
                throw new RegistroVinculadoException(
                    'Não é possível excluir: existem registros vinculados a este item.'
                );
            }
            throw $e;
        }
    }

    /** Garante que salvar() recebeu o tipo certo de objeto. */
    protected function verificarTipo(object $entidade): void
    {
        $classe = $this->classeEntidade();
        if (!$entidade instanceof $classe) {
            throw new InvalidArgumentException('Este repositório só salva objetos ' . $classe);
        }
    }
}
