<?php

declare(strict_types=1);

namespace Gamez\Duration\Exception;

use Gamez\Duration\DurationException;
use InvalidArgumentException;
use Throwable;

final class InvalidDuration extends InvalidArgumentException implements DurationException
{
    public static function because($reason, int $code = null, Throwable $previous = null): self
    {
        return new self($reason, $code ?: 0, $previous);
    }
}
