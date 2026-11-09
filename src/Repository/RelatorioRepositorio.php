<?php
declare(strict_types=1);

namespace App\Repository;

use PDO;

/**
 * Consultas de relatório (somente leitura).
 * Cada consulta soma os valores no próprio banco (SUM + GROUP BY)
 * e o PHP só junta os resultados em uma lista para a tela.
 */
class RelatorioRepositorio
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Custo de combustível e manutenção de cada secretaria no período.
     * Devolve uma lista de arrays com: secretaria, sigla, combustivel, manutencao.
     */
    public function custoPorSecretaria(string $inicio, string $fim): array
    {
        // Total de combustível agrupado por secretaria
        $combustivel = $this->somar(
            'SELECT v.secretaria_id AS chave, SUM(a.valor_total) AS total
               FROM abastecimento a
               JOIN veiculo v ON v.id = a.veiculo_id
              WHERE a.data BETWEEN :inicio AND :fim
              GROUP BY v.secretaria_id',
            $inicio,
            $fim
        );

        // Total de manutenção (quantidade x valor de cada item) agrupado por secretaria
        $manutencao = $this->somar(
            'SELECT v.secretaria_id AS chave, SUM(i.quantidade * i.valor_unitario) AS total
               FROM item_servico i
               JOIN ordem_manutencao o ON o.id = i.ordem_id
               JOIN veiculo v ON v.id = o.veiculo_id
              WHERE o.data BETWEEN :inicio AND :fim
              GROUP BY v.secretaria_id',
            $inicio,
            $fim
        );

        // Lista todas as secretarias, inclusive as que não gastaram nada (ficam com 0)
        $secretarias = $this->pdo->query('SELECT id, nome, sigla FROM secretaria ORDER BY nome')->fetchAll();

        $resultado = [];
        foreach ($secretarias as $s) {
            $resultado[] = [
                'secretaria'  => $s['nome'],
                'sigla'       => $s['sigla'],
                'combustivel' => $combustivel[$s['id']] ?? 0.0,
                'manutencao'  => $manutencao[$s['id']] ?? 0.0,
            ];
        }
        return $resultado;
    }

    /**
     * Custo de cada veículo no período.
     * Devolve uma lista de arrays com: descricao, placa, sigla, litros, combustivel, manutencao.
     */
    public function custoPorVeiculo(string $inicio, string $fim): array
    {
        $litros = $this->somar(
            'SELECT veiculo_id AS chave, SUM(litros) AS total
               FROM abastecimento
              WHERE data BETWEEN :inicio AND :fim
              GROUP BY veiculo_id',
            $inicio,
            $fim
        );

        $combustivel = $this->somar(
            'SELECT veiculo_id AS chave, SUM(valor_total) AS total
               FROM abastecimento
              WHERE data BETWEEN :inicio AND :fim
              GROUP BY veiculo_id',
            $inicio,
            $fim
        );

        $manutencao = $this->somar(
            'SELECT o.veiculo_id AS chave, SUM(i.quantidade * i.valor_unitario) AS total
               FROM item_servico i
               JOIN ordem_manutencao o ON o.id = i.ordem_id
              WHERE o.data BETWEEN :inicio AND :fim
              GROUP BY o.veiculo_id',
            $inicio,
            $fim
        );

        $veiculos = $this->pdo->query(
            'SELECT v.id, v.descricao, v.placa, s.sigla
               FROM veiculo v
               JOIN secretaria s ON s.id = v.secretaria_id
              ORDER BY s.sigla, v.descricao'
        )->fetchAll();

        $resultado = [];
        foreach ($veiculos as $v) {
            $resultado[] = [
                'descricao'   => $v['descricao'],
                'placa'       => $v['placa'],
                'sigla'       => $v['sigla'],
                'litros'      => $litros[$v['id']] ?? 0.0,
                'combustivel' => $combustivel[$v['id']] ?? 0.0,
                'manutencao'  => $manutencao[$v['id']] ?? 0.0,
            ];
        }
        return $resultado;
    }

    /**
     * Roda uma consulta de soma no período e devolve um array [chave => total].
     * A consulta precisa ter as colunas "chave" e "total" e os parâmetros :inicio e :fim.
     */
    private function somar(string $sql, string $inicio, string $fim): array
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':inicio' => $inicio, ':fim' => $fim]);

        $totais = [];
        foreach ($stmt->fetchAll() as $linha) {
            $totais[$linha['chave']] = (float) $linha['total'];
        }
        return $totais;
    }
}
