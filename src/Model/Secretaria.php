<?php
declare(strict_types=1);

namespace App\Model;

use App\Traits\Auditavel;
use InvalidArgumentException;

/**
 * Secretaria da Prefeitura.
 *
 * AGREGAÇÃO com Veiculo: a secretaria "tem" veículos, mas os veículos
 * são criados fora dela e continuam existindo sem ela
 * (um veículo pode ser transferido para outra secretaria).
 */
class Secretaria
{
    use Auditavel;

    private ?int $id;
    private string $nome;
    private string $sigla;

    /** @var Veiculo[] */
    private array $veiculos = [];

    public function __construct(string $nome, string $sigla, ?int $id = null)
    {
        $this->setNome($nome);
        $this->setSigla($sigla);
        $this->id = $id;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getNome(): string
    {
        return $this->nome;
    }

    public function setNome(string $nome): void
    {
        $nome = trim($nome);
        if (mb_strlen($nome) < 3) {
            throw new InvalidArgumentException('O nome da secretaria deve ter pelo menos 3 caracteres.');
        }
        $this->nome = $nome;
    }

    public function getSigla(): string
    {
        return $this->sigla;
    }

    public function setSigla(string $sigla): void
    {
        $sigla = strtoupper(trim($sigla));
        if (!preg_match('/^[A-Z]{2,10}$/', $sigla)) {
            throw new InvalidArgumentException('A sigla deve ter de 2 a 10 letras.');
        }
        $this->sigla = $sigla;
    }

    /** Agregação: recebe um veículo que já existe. */
    public function adicionarVeiculo(Veiculo $veiculo): void
    {
        $this->veiculos[] = $veiculo;
    }

    /** @return Veiculo[] */
    public function getVeiculos(): array
    {
        return $this->veiculos;
    }

    public function quantidadeVeiculos(): int
    {
        return count($this->veiculos);
    }
}
