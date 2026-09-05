<?php

declare(strict_types=1);

namespace Lebensbaum\ContaoDomainManagerBundle\Event;

final class InstallationSynchronizedEvent
{
    public function __construct(
        public readonly int $installationId,
        public readonly string $domain,
        public readonly string $oldContaoVersion,
        public readonly string $newContaoVersion,
        public readonly string $oldPhpVersion,
        public readonly string $newPhpVersion,
        public readonly int $synchronizedAt,
    ) {
    }

    public function hasContaoVersionChanged(): bool
    {
        return $this->oldContaoVersion !== $this->newContaoVersion;
    }

    public function hasPhpVersionChanged(): bool
    {
        return $this->oldPhpVersion !== $this->newPhpVersion;
    }
}
