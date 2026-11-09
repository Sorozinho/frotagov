<?php
declare(strict_types=1);

namespace App\Model;

use InvalidArgumentException;

/**
 * Item de uma ordem de manutenção (peça ou serviço).
 * Só faz sentido DENTRO de uma OrdemManutencao (composição) —
 * por isso quem cria os itens é a própria ordem (OrdemManutencao::adicionarItem).
 */
class ItemServico
{
    public const PECA    = 'peca';
    public const SERVICO = 'servico';

    private string $tipo;
    private string $descricao;
    private float $quantidade;
    private float $valorUnitario;

    public function __construct(string $tipo, string $descricao, float $quantidade, float $valorUnitario)
    {
        if (!in_array($tipo, [self::PECA, self::SERVICO], true)) {
            throw new InvalidArgumentException('Tipo de item inválido.');
        }
        if (trim($descricao) === '') {
            throw new InvalidArgumentException('Informe a descrição do item.');
        }
        if ($quantidade <= 0) {
            throw new InvalidArgumentException('A quantidade do item deve ser maior que zero.');
        }
        if ($valorUnitario < 0) {
            throw new InvalidArgumentException('O valor do item não pode ser negativo.');
        }
        $this->tipo          = $tipo;
        $this->descricao     = trim($descricao);
        $this->quantidade    = $quantidade;
        $this->valorUnitario = $valorUnitario;
    }

    public function subtotal(): float
    {
        return $this->quantidade * $this->valorUnitario;
    }

    public function getTipo(): string
    {
        return $this->tipo;
    }

    public function getTipoDescricao(): string
    {
        return $this->tipo === self::PECA ? 'Peça' : 'Serviço';
    }

    public function getDescricao(): string
    {
        return $this->descricao;
    }

    public function getQuantidade(): float
    {
        return $this->quantidade;
    }

    public function getValorUnitario(): float
    {
        return $this->valorUnitario;
    }
}
