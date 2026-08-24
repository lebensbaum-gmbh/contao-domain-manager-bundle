<?php

declare(strict_types=1);

namespace Lebensbaum\ContaoDomainManagerBundle\Dca;

final class SettingsCallbacks
{
    public function normalizeOptionalTimestamp(mixed $value): mixed
    {
        return (int) $value > 0 ? $value : '';
    }
}
