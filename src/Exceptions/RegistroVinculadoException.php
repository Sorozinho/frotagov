<?php
declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/** Lançada quando tentam excluir algo que outros registros usam. */
class RegistroVinculadoException extends RuntimeException
{
}
