<?php
declare(strict_types=1);

namespace App\Repository;

use App\Model\OrdemManutencao;
use PDO;
use Throwable;

class OrdemManutencaoRepositorio extends RepositorioPdo
{
    private VeiculoRepositorio $veiculos;

    public function __construct(PDO $pdo, VeiculoRepositorio $veiculos)
    {
        parent::__construct($pdo); // a classe mãe guarda o PDO
        $this->veiculos = $veiculos;
    }

    protected function tabela(): string
    {
        return 'ordem_manutencao';
    }

    protected function classeEntidade(): string
    {
        return OrdemManutencao::class;
    }

    /**
     * Salva a ordem e TODOS os itens numa TRANSAÇÃO.
     * Se um item der erro, nada fica gravado (nem a ordem).
     * Isso protege a integridade: nunca fica ordem "pela metade" no banco.
     */
    public function salvar(object $entidade): void
    {
        $this->verificarTipo($entidade);
        /** @var OrdemManutencao $entidade */
        $entidade->validarParaSalvar();

        $dados = [
            ':veiculo'   => $entidade->getVeiculo()->getId(),
            ':data'      => $entidade->getData(),
            ':oficina'   => $entidade->getOficina(),
            ':descricao' => $entidade->getDescricao(),
        ];

        $this->pdo->beginTransaction();
        try {
            if ($entidade->getId() === null) {
                $sql = 'INSERT INTO ordem_manutencao (veiculo_id, data, oficina, descricao, criado_em)
                        VALUES (:veiculo, :data, :oficina, :descricao, :criado)';
                $entidade->marcarCriacao();
                $dados[':criado'] = $entidade->getCriadoEm()->format('Y-m-d H:i:s');
                $this->pdo->prepare($sql)->execute($dados);
                $entidade->setId((int) $this->pdo->lastInsertId());
            } else {
                $sql = 'UPDATE ordem_manutencao SET veiculo_id = :veiculo, data = :data, oficina = :oficina,
                               descricao = :descricao, atualizado_em = :atualizado
                        WHERE id = :id';
                $entidade->marcarAtualizacao();
                $dados[':atualizado'] = $entidade->getAtualizadoEm()->format('Y-m-d H:i:s');
                $dados[':id'] = $entidade->getId();
                $this->pdo->prepare($sql)->execute($dados);

                // Na edição: apaga os itens antigos e grava a lista nova
                $this->pdo->prepare('DELETE FROM item_servico WHERE ordem_id = :id')
                          ->execute([':id' => $entidade->getId()]);
            }

            $stmtItem = $this->pdo->prepare(
                'INSERT INTO item_servico (ordem_id, tipo, descricao, quantidade, valor_unitario)
                 VALUES (:ordem, :tipo, :descricao, :qtd, :valor)'
            );
            foreach ($entidade->getItens() as $item) {
                $stmtItem->execute([
                    ':ordem'     => $entidade->getId(),
                    ':tipo'      => $item->getTipo(),
                    ':descricao' => $item->getDescricao(),
                    ':qtd'       => $item->getQuantidade(),
                    ':valor'     => $item->getValorUnitario(),
                ]);
            }

            $this->pdo->commit();
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function buscarPorId(int $id): ?OrdemManutencao
    {
        $stmt = $this->pdo->prepare('SELECT * FROM ordem_manutencao WHERE id = :id');
        $stmt->execute([':id' => $id]);
        $linha = $stmt->fetch();
        return $linha ? $this->montar($linha) : null;
    }

    /** @return OrdemManutencao[] */
    public function listarTodos(): array
    {
        $linhas = $this->pdo->query('SELECT * FROM ordem_manutencao ORDER BY data DESC, id DESC')->fetchAll();
        $lista  = [];
        foreach ($linhas as $linha) {
            $lista[] = $this->montar($linha);
        }
        return $lista;
    }

    private function montar(array $l): OrdemManutencao
    {
        $veiculo = $this->veiculos->buscarPorId((int) $l['veiculo_id']);
        $ordem = new OrdemManutencao($veiculo, $l['data'], $l['oficina'], $l['descricao'], (int) $l['id']);
        $ordem->definirDatasAuditoria($l['criado_em'], $l['atualizado_em']);

        $stmt = $this->pdo->prepare('SELECT * FROM item_servico WHERE ordem_id = :id ORDER BY id');
        $stmt->execute([':id' => $l['id']]);
        foreach ($stmt->fetchAll() as $i) {
            // os itens são recriados PELA ordem (composição)
            $ordem->adicionarItem($i['tipo'], $i['descricao'], (float) $i['quantidade'], (float) $i['valor_unitario']);
        }
        return $ordem;
    }
}
