<?php
declare(strict_types=1);

namespace App\Repository;

use App\Model\Automovel;
use App\Model\MaquinaPesada;
use App\Model\Veiculo;
use UnexpectedValueException;

class VeiculoRepositorio extends RepositorioPdo
{
    protected function tabela(): string
    {
        return 'veiculo';
    }

    protected function classeEntidade(): string
    {
        return Veiculo::class; // aceita Automovel E MaquinaPesada, porque os dois "são" Veiculo
    }

    public function salvar(object $entidade): void
    {
        $this->verificarTipo($entidade);
        /** @var Veiculo $entidade */

        $dados = [
            ':secretaria' => $entidade->getSecretariaId(),
            ':tipo'       => $entidade->getTipo(),        // polimorfismo: cada classe devolve o seu
            ':placa'      => $entidade->getPlaca(),       // null para máquina
            ':descricao'  => $entidade->getDescricao(),
            ':medidor'    => $entidade->getMedidorAtual(),
        ];

        if ($entidade->getId() === null) {
            $sql = 'INSERT INTO veiculo (secretaria_id, tipo, placa, descricao, medidor_atual, criado_em)
                    VALUES (:secretaria, :tipo, :placa, :descricao, :medidor, :criado)';
            $entidade->marcarCriacao();
            $dados[':criado'] = $entidade->getCriadoEm()->format('Y-m-d H:i:s');
            $this->pdo->prepare($sql)->execute($dados);
            $entidade->setId((int) $this->pdo->lastInsertId());
        } else {
            $sql = 'UPDATE veiculo SET secretaria_id = :secretaria, tipo = :tipo, placa = :placa,
                           descricao = :descricao, medidor_atual = :medidor, atualizado_em = :atualizado
                    WHERE id = :id';
            $entidade->marcarAtualizacao();
            $dados[':atualizado'] = $entidade->getAtualizadoEm()->format('Y-m-d H:i:s');
            $dados[':id'] = $entidade->getId();
            $this->pdo->prepare($sql)->execute($dados);
        }
    }

    public function buscarPorId(int $id): ?Veiculo
    {
        $stmt = $this->pdo->prepare('SELECT * FROM veiculo WHERE id = :id');
        $stmt->execute([':id' => $id]);
        $linha = $stmt->fetch();
        return $linha ? self::montar($linha) : null;
    }

    /** @return Veiculo[] */
    public function listarTodos(): array
    {
        $linhas = $this->pdo->query('SELECT * FROM veiculo ORDER BY descricao')->fetchAll();
        $lista  = [];
        foreach ($linhas as $linha) {
            $lista[] = self::montar($linha);
        }
        return $lista;
    }

    /** @return Veiculo[] */
    public function listarPorSecretaria(int $secretariaId): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM veiculo WHERE secretaria_id = :s ORDER BY descricao');
        $stmt->execute([':s' => $secretariaId]);
        $lista = [];
        foreach ($stmt->fetchAll() as $linha) {
            $lista[] = self::montar($linha);
        }
        return $lista;
    }

    /**
     * Atualiza só o hodômetro/horímetro (usado ao registrar abastecimento).
     */
    public function atualizarMedidor(Veiculo $veiculo): void
    {
        $stmt = $this->pdo->prepare('UPDATE veiculo SET medidor_atual = :m WHERE id = :id');
        $stmt->execute([':m' => $veiculo->getMedidorAtual(), ':id' => $veiculo->getId()]);
    }

    /**
     * "Fábrica": olha a coluna tipo e cria o objeto da classe certa.
     * Quem chama recebe um Veiculo e não precisa saber qual é a classe real.
     */
    public static function montar(array $l): Veiculo
    {
        $veiculo = match ($l['tipo']) {
            'automovel' => new Automovel(
                (int) $l['secretaria_id'], $l['descricao'], (float) $l['medidor_atual'], (string) $l['placa'], (int) $l['id']
            ),
            'maquina' => new MaquinaPesada(
                (int) $l['secretaria_id'], $l['descricao'], (float) $l['medidor_atual'], (int) $l['id']
            ),
            default => throw new UnexpectedValueException('Tipo de veículo desconhecido: ' . $l['tipo']),
        };
        $veiculo->definirDatasAuditoria($l['criado_em'], $l['atualizado_em']);
        return $veiculo;
    }
}
