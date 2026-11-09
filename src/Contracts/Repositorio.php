<?php
declare(strict_types=1);

namespace App\Contracts;

/**
 * INTERFACE: um "contrato". Toda classe que implementa Repositorio
 * é obrigada a ter esses 4 métodos (o CRUD).
 * Assim todos os repositórios funcionam do mesmo jeito.
 */
interface Repositorio
{
    /** Insere (se não tem id) ou atualiza (se já tem id). */
    public function salvar(object $entidade): void;

    public function buscarPorId(int $id): ?object;

    /** @return object[] */
    public function listarTodos(): array;

    public function excluir(int $id): void;
}
