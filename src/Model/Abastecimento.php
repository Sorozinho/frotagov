<?php
declare(strict_types=1);

namespace App\Model;

use App\Traits\Auditavel;
use DateTimeImmutable;
use InvalidArgumentException;

/**
 * ASSOCIAÇÃO com Veiculo: o abastecimento "usa" um veículo,
 * mas os dois são independentes (o veículo existe antes e depois).
 */
class Abastecimento
{
    use Auditavel;

    private ?int $id;
    private Veiculo $veiculo;   // guarda o OBJETO, não só o id
    private string $data;
    private float $litros;
    private float $valorTotal;
    private float $medidor;
    private string $posto;

    public function __construct(
        Veiculo $veiculo,
        string $data,
        float $litros,
        float $valorTotal,
        float $medidor,
        string $posto,
        ?int $id = null
    ) {
        $this->veiculo = $veiculo;
        $this->setData($data);
        $this->setLitros($litros);
        $this->setValorTotal($valorTotal);
        $this->setMedidor($medidor);
        $this->setPosto($posto);
        $this->id = $id;
    }

    public function precoPorLitro(): float
    {
        return $this->valorTotal / $this->litros;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getVeiculo(): Veiculo
    {
        return $this->veiculo;
    }

    public function setVeiculo(Veiculo $veiculo): void
    {
        $this->veiculo = $veiculo;
    }

    public function getData(): string
    {
        return $this->data;
    }

    public function setData(string $data): void
    {
        $d = DateTimeImmutable::createFromFormat('Y-m-d', $data);
        if (!$d || $d->format('Y-m-d') !== $data) {
            throw new InvalidArgumentException('Data inválida.');
        }
        if ($d > new DateTimeImmutable('today')) {
            throw new InvalidArgumentException('A data do abastecimento não pode ser no futuro.');
        }
        $this->data = $data;
    }

    public function getLitros(): float
    {
        return $this->litros;
    }

    public function setLitros(float $litros): void
    {
        if ($litros <= 0) {
            throw new InvalidArgumentException('A quantidade de litros deve ser maior que zero.');
        }
        $this->litros = $litros;
    }

    public function getValorTotal(): float
    {
        return $this->valorTotal;
    }

    public function setValorTotal(float $valorTotal): void
    {
        if ($valorTotal <= 0) {
            throw new InvalidArgumentException('O valor total deve ser maior que zero.');
        }
        $this->valorTotal = $valorTotal;
    }

    public function getMedidor(): float
    {
        return $this->medidor;
    }

    public function setMedidor(float $medidor): void
    {
        if ($medidor < 0) {
            throw new InvalidArgumentException('O hodômetro/horímetro não pode ser negativo.');
        }
        $this->medidor = $medidor;
    }

    public function getPosto(): string
    {
        return $this->posto;
    }

    public function setPosto(string $posto): void
    {
        $posto = trim($posto);
        if ($posto === '') {
            throw new InvalidArgumentException('Informe o posto.');
        }
        $this->posto = $posto;
    }
}
