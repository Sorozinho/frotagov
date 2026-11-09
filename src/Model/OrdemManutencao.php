<?php
declare(strict_types=1);

namespace App\Model;

use App\Traits\Auditavel;
use DateTimeImmutable;
use InvalidArgumentException;

/**
 * Ordem de manutenção de um veículo.
 *
 * COMPOSIÇÃO com ItemServico: a ordem é "dona" dos itens.
 *   - Os itens são criados DENTRO da ordem (adicionarItem cria o objeto)
 *   - Não existe item sem ordem; se a ordem é excluída, os itens vão junto
 *     (no banco: ON DELETE CASCADE)
 *
 * ASSOCIAÇÃO com Veiculo: a ordem se refere a um veículo que existe por conta própria.
 */
class OrdemManutencao
{
    use Auditavel;

    private ?int $id;
    private Veiculo $veiculo;
    private string $data;
    private string $oficina;
    private string $descricao;

    /** @var ItemServico[] */
    private array $itens = [];

    public function __construct(Veiculo $veiculo, string $data, string $oficina, string $descricao, ?int $id = null)
    {
        $this->veiculo = $veiculo;
        $this->setData($data);
        $this->setOficina($oficina);
        $this->setDescricao($descricao);
        $this->id = $id;
    }

    /** Composição: o item nasce aqui dentro, ninguém de fora faz "new ItemServico". */
    public function adicionarItem(string $tipo, string $descricao, float $quantidade, float $valorUnitario): ItemServico
    {
        $item = new ItemServico($tipo, $descricao, $quantidade, $valorUnitario);
        $this->itens[] = $item;
        return $item;
    }

    public function limparItens(): void
    {
        $this->itens = [];
    }

    /** @return ItemServico[] */
    public function getItens(): array
    {
        return $this->itens;
    }

    public function valorTotal(): float
    {
        $total = 0.0;
        foreach ($this->itens as $item) {
            $total += $item->subtotal();
        }
        return $total;
    }

    /** Regra de negócio: não existe ordem vazia. */
    public function validarParaSalvar(): void
    {
        if (count($this->itens) === 0) {
            throw new InvalidArgumentException('A ordem de manutenção precisa ter pelo menos um item.');
        }
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
        $this->data = $data;
    }

    public function getOficina(): string
    {
        return $this->oficina;
    }

    public function setOficina(string $oficina): void
    {
        $oficina = trim($oficina);
        if ($oficina === '') {
            throw new InvalidArgumentException('Informe a oficina.');
        }
        $this->oficina = $oficina;
    }

    public function getDescricao(): string
    {
        return $this->descricao;
    }

    public function setDescricao(string $descricao): void
    {
        $descricao = trim($descricao);
        if ($descricao === '') {
            throw new InvalidArgumentException('Informe a descrição da ordem.');
        }
        $this->descricao = $descricao;
    }
}
