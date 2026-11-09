<?php
declare(strict_types=1);

namespace App\Repository;

use App\Model\Secretaria;

class SecretariaRepositorio extends RepositorioPdo
{
    protected function tabela(): string
    {
        return 'secretaria';
    }

    protected function classeEntidade(): string
    {
        return Secretaria::class;
    }

    public function salvar(object $entidade): void
    {
        $this->verificarTipo($entidade);
        /** @var Secretaria $entidade */

        $dados = [
            ':nome'  => $entidade->getNome(),
            ':sigla' => $entidade->getSigla(),
        ];

        if ($entidade->getId() === null) {
            $sql = 'INSERT INTO secretaria (nome, sigla, criado_em) VALUES (:nome, :sigla, :criado)';
            $entidade->marcarCriacao();
            $dados[':criado'] = $entidade->getCriadoEm()->format('Y-m-d H:i:s');
            $this->pdo->prepare($sql)->execute($dados);
            $entidade->setId((int) $this->pdo->lastInsertId());
        } else {
            $sql = 'UPDATE secretaria SET nome = :nome, sigla = :sigla, atualizado_em = :atualizado WHERE id = :id';
            $entidade->marcarAtualizacao();
            $dados[':atualizado'] = $entidade->getAtualizadoEm()->format('Y-m-d H:i:s');
            $dados[':id'] = $entidade->getId();
            $this->pdo->prepare($sql)->execute($dados);
        }
    }

    public function buscarPorId(int $id): ?Secretaria
    {
        $stmt = $this->pdo->prepare('SELECT * FROM secretaria WHERE id = :id');
        $stmt->execute([':id' => $id]);
        $linha = $stmt->fetch();
        return $linha ? $this->montar($linha) : null;
    }

    /** @return Secretaria[] */
    public function listarTodos(): array
    {
        $linhas = $this->pdo->query('SELECT * FROM secretaria ORDER BY nome')->fetchAll();
        $lista  = [];
        foreach ($linhas as $linha) {
            $lista[] = $this->montar($linha);
        }
        return $lista;
    }

    /**
     * Busca a secretaria já com seus veículos (agregação).
     */
    public function buscarComVeiculos(int $id, VeiculoRepositorio $veiculos): ?Secretaria
    {
        $secretaria = $this->buscarPorId($id);
        if ($secretaria === null) {
            return null;
        }
        foreach ($veiculos->listarPorSecretaria($id) as $veiculo) {
            $secretaria->adicionarVeiculo($veiculo);
        }
        return $secretaria;
    }

    /** Transforma uma linha do banco em objeto. */
    private function montar(array $l): Secretaria
    {
        $s = new Secretaria($l['nome'], $l['sigla'], (int) $l['id']);
        $s->definirDatasAuditoria($l['criado_em'], $l['atualizado_em']);
        return $s;
    }
}
