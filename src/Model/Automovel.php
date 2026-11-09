<?php
declare(strict_types=1);

namespace App\Model;

use InvalidArgumentException;

/**
 * HERANÇA: Automovel "é um" Veiculo (extends).
 * Mede uso em km, consumo em km/L e exige placa.
 */
class Automovel extends Veiculo
{
    private string $placa;

    public function __construct(int $secretariaId, string $descricao, float $medidorAtual, string $placa, ?int $id = null)
    {
        parent::__construct($secretariaId, $descricao, $medidorAtual, $id); // chama o construtor da classe mãe
        $this->setPlaca($placa);
    }

    public function getTipo(): string
    {
        return 'automovel';
    }

    public function getTipoDescricao(): string
    {
        return 'Automóvel';
    }

    public function getUnidadeMedidor(): string
    {
        return 'km';
    }

    public function getUnidadeConsumo(): string
    {
        return 'km/L';
    }

    public function intervaloRevisao(): float
    {
        return 10000; // revisão a cada 10.000 km
    }

    /** km rodados ÷ litros = km/L */
    protected function calcularConsumo(float $usoNoPeriodo, float $litros): float
    {
        return $usoNoPeriodo / $litros;
    }

    public function getPlaca(): ?string
    {
        return $this->placa;
    }

    public function setPlaca(string $placa): void
    {
        $placa = strtoupper(str_replace(['-', ' '], '', $placa));
        // Aceita padrão antigo (ABC1234) e Mercosul (ABC1D23)
        if (!preg_match('/^[A-Z]{3}[0-9][A-Z0-9][0-9]{2}$/', $placa)) {
            throw new InvalidArgumentException('Placa inválida. Use o formato ABC1234 ou ABC1D23.');
        }
        $this->placa = $placa;
    }
}
