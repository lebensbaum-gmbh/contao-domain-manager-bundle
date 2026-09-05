<?php

declare(strict_types=1);

namespace Lebensbaum\ContaoDomainManagerBundle\Tests\Event;

use Lebensbaum\ContaoDomainManagerBundle\Event\InstallationSynchronizedEvent;
use PHPUnit\Framework\TestCase;

final class InstallationSynchronizedEventTest extends TestCase
{
    public function testVersionChangesAreDetected(): void
    {
        $event = new InstallationSynchronizedEvent(
            12,
            'example.org',
            '5.7.10',
            '5.7.11',
            '8.4.10',
            '8.5.0',
            1_000_000,
        );

        self::assertTrue($event->hasContaoVersionChanged());
        self::assertTrue($event->hasPhpVersionChanged());
    }

    public function testUnchangedVersionsAreNotReportedAsChanges(): void
    {
        $event = new InstallationSynchronizedEvent(
            12,
            'example.org',
            '5.7.11',
            '5.7.11',
            '8.5.0',
            '8.5.0',
            1_000_000,
        );

        self::assertFalse($event->hasContaoVersionChanged());
        self::assertFalse($event->hasPhpVersionChanged());
    }
}
