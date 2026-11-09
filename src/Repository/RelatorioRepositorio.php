<?php
declare(strict_types=1);

namespace App\Repository;

use PDO;

/**
 * Consultas de relatório (somente leitura).
 * Usa GROUP BY e SUM no próprio banco, que é mais rápido que somar no PHP.
 */
class RelatorioRepositorio
{
    public function __construct(private PDO $pdo)
    {
    }

    /**
     * Custo de combustível e manutenção por secretaria no período.
     * @return array<int, array{secretaria:string, sigla:string, combustivel:float, manutencao:float}>
     */
    public function custoPorSecretaria(string $inicio, string $fim): array
    {
        $sql = "SELECT s.nome AS secretaria, s.sigla,
                       COALESCE((SELECT SUM(a.valor_total)
                                   FROM abastecimento a
                                   JOIN veiculo v ON v.id = a.veiculo_id
                                  WHERE v.secretaria_id = s.id
                                    AND a.data BETWEEN :i1 AND :f1), 0) AS combustivel,
                       COALESCE((SELECT SUM(it.quantidade * it.valor_unitario)
                                   FROM item_servico it
                                   JOIN ordem_manutencao o ON o.id = it.ordem_id
                                   JOIN veiculo v ON v.id = o.veiculo_id
                                  WHERE v.secretaria_id = s.id
                                    AND o.data BETWEEN :i2 AND :f2), 0) AS manutencao
                  FROM secretaria s
                 ORDER BY s.nome";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':i1' => $inicio, ':f1' => $fim, ':i2' => $inicio, ':f2' => $fim]);
        return $stmt->fetchAll();
    }

    /**
     * Custo por veículo no período.
     * @return array<int, array{descricao:string, placa:?string, sigla:string, litros:float, combustivel:float, manutencao:float}>
     */
    public function custoPorVeiculo(string $inicio, string $fim): array
    {
        $sql = "SELECT v.descricao, v.placa, s.sigla,
                       COALESCE((SELECT SUM(a.litros) FROM abastecimento a
                                  WHERE a.veiculo_id = v.id AND a.data BETWEEN :i1 AND :f1), 0) AS litros,
                       COALESCE((SELECT SUM(a.valor_total) FROM abastecimento a
                                  WHERE a.veiculo_id = v.id AND a.data BETWEEN :i2 AND :f2), 0) AS combustivel,
                       COALESCE((SELECT SUM(it.quantidade * it.valor_unitario)
                                   FROM item_servico it
                                   JOIN ordem_manutencao o ON o.id = it.ordem_id
                                  WHERE o.veiculo_id = v.id AND o.data BETWEEN :i3 AND :f3), 0) AS manutencao
                  FROM veiculo v
                  JOIN secretaria s ON s.id = v.secretaria_id
                 ORDER BY s.sigla, v.descricao";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':i1' => $inicio, ':f1' => $fim,
            ':i2' => $inicio, ':f2' => $fim,
            ':i3' => $inicio, ':f3' => $fim,
        ]);
        return $stmt->fetchAll();
    }
}
