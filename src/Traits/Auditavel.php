<?php
declare(strict_types=1);

namespace App\Traits;

use DateTimeImmutable;

/**
 * TRAIT: um bloco de código que é "colado" dentro de várias classes.
 * Secretaria, Veiculo, Abastecimento e OrdemManutencao usam este trait
 * para ter as datas de criação e de última alteração, sem repetir código
 * e sem precisar herdar de uma classe em comum.
 */
trait Auditavel
{
    private ?DateTimeImmutable $criadoEm = null;
    private ?DateTimeImmutable $atualizadoEm = null;

    /** Chamado pelo repositório antes de um INSERT. */
    public function marcarCriacao(): void
    {
        $this->criadoEm = new DateTimeImmutable();
    }

    /** Chamado pelo repositório antes de um UPDATE. */
    public function marcarAtualizacao(): void
    {
        $this->atualizadoEm = new DateTimeImmutable();
    }

    /** Usado pelo repositório ao ler do banco. */
    public function definirDatasAuditoria(?string $criadoEm, ?string $atualizadoEm): void
    {
        $this->criadoEm     = $criadoEm ? new DateTimeImmutable($criadoEm) : null;
        $this->atualizadoEm = $atualizadoEm ? new DateTimeImmutable($atualizadoEm) : null;
    }

    public function getCriadoEm(): ?DateTimeImmutable
    {
        return $this->criadoEm;
    }

    public function getAtualizadoEm(): ?DateTimeImmutable
    {
        return $this->atualizadoEm;
    }
}
