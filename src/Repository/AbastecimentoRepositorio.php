<?php
declare(strict_types=1);

namespace App\Repository;

use App\Model\Abastecimento;
use PDO;
use Throwable;

class AbastecimentoRepositorio extends RepositorioPdo
{
    private VeiculoRepositorio $veiculos;

    public function __construct(PDO $pdo, VeiculoRepositorio $veiculos)
    {
        parent::__construct($pdo); // a classe mãe guarda o PDO
        $this->veiculos = $veiculos;
    }

    protected function tabela(): string
    {
        return 'abastecimento';
    }

    protected function classeEntidade(): string
    {
        return Abastecimento::class;
    }

    /**
     * Salva o abastecimento E atualiza o hodômetro do veículo.
     * São 2 comandos SQL que precisam acontecer juntos -> TRANSAÇÃO.
     */
    public function salvar(object $entidade): void
    {
        $this->verificarTipo($entidade);
        /** @var Abastecimento $entidade */

        $dados = [
            ':veiculo' => $entidade->getVeiculo()->getId(),
            ':data'    => $entidade->getData(),
            ':litros'  => $entidade->getLitros(),
            ':valor'   => $entidade->getValorTotal(),
            ':medidor' => $entidade->getMedidor(),
            ':posto'   => $entidade->getPosto(),
        ];

        $this->pdo->beginTransaction();
        try {
            if ($entidade->getId() === null) {
                $sql = 'INSERT INTO abastecimento (veiculo_id, data, litros, valor_total, medidor, posto, criado_em)
                        VALUES (:veiculo, :data, :litros, :valor, :medidor, :posto, :criado)';
                $entidade->marcarCriacao();
                $dados[':criado'] = $entidade->getCriadoEm()->format('Y-m-d H:i:s');
                $this->pdo->prepare($sql)->execute($dados);
                $entidade->setId((int) $this->pdo->lastInsertId());
            } else {
                $sql = 'UPDATE abastecimento SET veiculo_id = :veiculo, data = :data, litros = :litros,
                               valor_total = :valor, medidor = :medidor, posto = :posto, atualizado_em = :atualizado
                        WHERE id = :id';
                $entidade->marcarAtualizacao();
                $dados[':atualizado'] = $entidade->getAtualizadoEm()->format('Y-m-d H:i:s');
                $dados[':id'] = $entidade->getId();
                $this->pdo->prepare($sql)->execute($dados);
            }

            $veiculo = $entidade->getVeiculo();
            $veiculo->registrarLeitura($entidade->getMedidor()); // só sobe se for maior
            $this->veiculos->atualizarMedidor($veiculo);

            $this->pdo->commit();   // deu tudo certo: confirma os 2 comandos
        } catch (Throwable $e) {
            $this->pdo->rollBack(); // algo falhou: desfaz tudo
            throw $e;
        }
    }

    public function buscarPorId(int $id): ?Abastecimento
    {
        $stmt = $this->pdo->prepare($this->sqlBase() . ' WHERE a.id = :id');
        $stmt->execute([':id' => $id]);
        $linha = $stmt->fetch();
        return $linha ? $this->montar($linha) : null;
    }

    /** @return Abastecimento[] */
    public function listarTodos(): array
    {
        $linhas = $this->pdo->query($this->sqlBase() . ' ORDER BY a.data DESC, a.id DESC')->fetchAll();
        $lista  = [];
        foreach ($linhas as $linha) {
            $lista[] = $this->montar($linha);
        }
        return $lista;
    }

    /** @return Abastecimento[] */
    public function listarPorVeiculo(int $veiculoId): array
    {
        $stmt = $this->pdo->prepare($this->sqlBase() . ' WHERE a.veiculo_id = :v ORDER BY a.medidor');
        $stmt->execute([':v' => $veiculoId]);
        $lista = [];
        foreach ($stmt->fetchAll() as $linha) {
            $lista[] = $this->montar($linha);
        }
        return $lista;
    }

    /**
     * JOIN: traz o abastecimento e o veículo numa consulta só.
     * Os campos do veículo vêm com prefixo "v_" para não misturar.
     */
    private function sqlBase(): string
    {
        return 'SELECT a.*, v.id AS v_id, v.secretaria_id AS v_secretaria_id, v.tipo AS v_tipo,
                       v.placa AS v_placa, v.descricao AS v_descricao, v.medidor_atual AS v_medidor_atual,
                       v.criado_em AS v_criado_em, v.atualizado_em AS v_atualizado_em
                FROM abastecimento a
                JOIN veiculo v ON v.id = a.veiculo_id';
    }

    private function montar(array $l): Abastecimento
    {
        $veiculo = VeiculoRepositorio::montar([
            'id'            => $l['v_id'],
            'secretaria_id' => $l['v_secretaria_id'],
            'tipo'          => $l['v_tipo'],
            'placa'         => $l['v_placa'],
            'descricao'     => $l['v_descricao'],
            'medidor_atual' => $l['v_medidor_atual'],
            'criado_em'     => $l['v_criado_em'],
            'atualizado_em' => $l['v_atualizado_em'],
        ]);

        $a = new Abastecimento(
            $veiculo,
            $l['data'],
            (float) $l['litros'],
            (float) $l['valor_total'],
            (float) $l['medidor'],
            $l['posto'],
            (int) $l['id']
        );
        $a->definirDatasAuditoria($l['criado_em'], $l['atualizado_em']);
        return $a;
    }
}
