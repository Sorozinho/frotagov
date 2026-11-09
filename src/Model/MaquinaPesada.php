<?php
declare(strict_types=1);

namespace App\Model;

/**
 * HERANÇA: MaquinaPesada "é um" Veiculo.
 * Retroescavadeira, trator, motoniveladora... medem uso em HORAS (horímetro),
 * consumo em litros por hora, e normalmente não têm placa.
 */
class MaquinaPesada extends Veiculo
{
    public function getTipo(): string
    {
        return 'maquina';
    }

    public function getTipoDescricao(): string
    {
        return 'Máquina pesada';
    }

    public function getUnidadeMedidor(): string
    {
        return 'h';
    }

    public function getUnidadeConsumo(): string
    {
        return 'L/h';
    }

    public function intervaloRevisao(): float
    {
        return 250; // revisão a cada 250 horas
    }

    /** litros ÷ horas trabalhadas = L/h (conta "ao contrário" do automóvel) */
    protected function calcularConsumo(float $usoNoPeriodo, float $litros): float
    {
        return $litros / $usoNoPeriodo;
    }

    public function getPlaca(): ?string
    {
        return null;
    }
}
