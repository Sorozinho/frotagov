<?php
declare(strict_types=1);

namespace App\Model;

use App\Traits\Auditavel;
use InvalidArgumentException;

/**
 * CLASSE ABSTRATA: não dá para fazer "new Veiculo(...)".
 * Ela guarda o que TODO veículo tem em comum e obriga as filhas
 * (Automovel e MaquinaPesada) a implementarem os métodos abstratos.
 *
 * HERANÇA: Automovel e MaquinaPesada "são um" Veiculo.
 * POLIMORFISMO: o mesmo método (calcularConsumo, intervaloRevisao...)
 * dá resultados diferentes conforme o tipo real do objeto.
 */
abstract class Veiculo
{
    use Auditavel;

    // ENCAPSULAMENTO: atributos privados, só mudam pelos setters (que validam)
    private ?int $id;
    private int $secretariaId;
    private string $descricao;
    private float $medidorAtual;

    public function __construct(int $secretariaId, string $descricao, float $medidorAtual, ?int $id = null)
    {
        $this->setSecretariaId($secretariaId);
        $this->setDescricao($descricao);
        $this->setMedidorAtual($medidorAtual);
        $this->id = $id;
    }

    // ---------- Métodos abstratos: cada filha implementa do seu jeito ----------

    /** Valor gravado na coluna "tipo" do banco. */
    abstract public function getTipo(): string;

    /** Nome amigável do tipo. */
    abstract public function getTipoDescricao(): string;

    /** "km" para automóvel, "h" para máquina. */
    abstract public function getUnidadeMedidor(): string;

    /** "km/L" para automóvel, "L/h" para máquina. */
    abstract public function getUnidadeConsumo(): string;

    /** A cada quantos km ou horas precisa revisar. */
    abstract public function intervaloRevisao(): float;

    /** Fórmula de consumo, diferente para cada tipo. */
    abstract protected function calcularConsumo(float $usoNoPeriodo, float $litros): float;

    /** Placa: automóvel exige, máquina não. */
    abstract public function getPlaca(): ?string;

    // ---------- Métodos concretos: iguais para todos os veículos ----------

    /**
     * Consumo médio a partir dos abastecimentos (método do "tanque cheio"):
     * uso = medidor do último - medidor do primeiro
     * litros = soma dos abastecimentos, menos o primeiro (que só "zera" o tanque)
     *
     * Aqui está o POLIMORFISMO na prática: este método é igual para todos,
     * mas chama $this->calcularConsumo(), que muda conforme a classe filha.
     *
     * @param Abastecimento[] $abastecimentos
     */
    public function consumoMedio(array $abastecimentos): ?float
    {
        if (count($abastecimentos) < 2) {
            return null; // precisa de pelo menos 2 abastecimentos
        }

        usort($abastecimentos, fn(Abastecimento $a, Abastecimento $b) => $a->getMedidor() <=> $b->getMedidor());

        $primeiro = $abastecimentos[0];
        $ultimo   = $abastecimentos[count($abastecimentos) - 1];
        $uso      = $ultimo->getMedidor() - $primeiro->getMedidor();

        $litros = 0.0;
        foreach (array_slice($abastecimentos, 1) as $abastecimento) {
            $litros += $abastecimento->getLitros();
        }

        if ($uso <= 0 || $litros <= 0) {
            return null;
        }
        return $this->calcularConsumo($uso, $litros);
    }

    /** Em quantos km/horas vence a próxima revisão. */
    public function proximaRevisao(): float
    {
        $intervalo = $this->intervaloRevisao();
        return (floor($this->medidorAtual / $intervalo) + 1) * $intervalo;
    }

    /** True se faltam 10% do intervalo ou menos para a revisão. */
    public function revisaoProxima(): bool
    {
        return ($this->proximaRevisao() - $this->medidorAtual) <= $this->intervaloRevisao() * 0.10;
    }

    /** Atualiza o km/horímetro só se o novo valor for maior. */
    public function registrarLeitura(float $medidor): void
    {
        if ($medidor > $this->medidorAtual) {
            $this->medidorAtual = $medidor;
        }
    }

    // ---------- Getters e setters ----------

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getSecretariaId(): int
    {
        return $this->secretariaId;
    }

    public function setSecretariaId(int $secretariaId): void
    {
        if ($secretariaId <= 0) {
            throw new InvalidArgumentException('Selecione uma secretaria.');
        }
        $this->secretariaId = $secretariaId;
    }

    public function getDescricao(): string
    {
        return $this->descricao;
    }

    public function setDescricao(string $descricao): void
    {
        $descricao = trim($descricao);
        if (mb_strlen($descricao) < 3) {
            throw new InvalidArgumentException('A descrição do veículo deve ter pelo menos 3 caracteres.');
        }
        $this->descricao = $descricao;
    }

    public function getMedidorAtual(): float
    {
        return $this->medidorAtual;
    }

    public function setMedidorAtual(float $medidorAtual): void
    {
        if ($medidorAtual < 0) {
            throw new InvalidArgumentException('O hodômetro/horímetro não pode ser negativo.');
        }
        $this->medidorAtual = $medidorAtual;
    }
}
