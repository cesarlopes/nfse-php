<?php

declare(strict_types=1);

namespace Cesarlopes\NFSePHP\Infrastructure\Http;

use RuntimeException;

/**
 * Exceção para falhas em transporte HTTP (cURL).
 */
class RestException extends RuntimeException
{
    // Pode adicionar propriedades específicas, ex: httpCode, url etc.
}
