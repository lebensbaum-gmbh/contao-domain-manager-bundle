<?php

declare(strict_types=1);

namespace Lebensbaum\ContaoDomainManagerBundle\Tests\Util;

use Lebensbaum\ContaoDomainManagerBundle\Util\SystemValueNormalizer;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class SystemValueNormalizerTest extends TestCase
{
    public function testWebrootLabelUsesOnlyLastDirectory(): void
    {
        self::assertSame('/public', SystemValueNormalizer::webrootLabel('/www/htdocs/customer/example/public'));
        self::assertSame('/web', SystemValueNormalizer::webrootLabel('/var/www/example/web/'));
        self::assertSame('/public', SystemValueNormalizer::webrootLabel('C:\\sites\\example\\public\\'));
        self::assertSame('', SystemValueNormalizer::webrootLabel('   '));
    }

    public function testContaoVersionRemovesLeadingV(): void
    {
        self::assertSame('5.7.11', SystemValueNormalizer::contaoVersion('v5.7.11'));
        self::assertSame('4.13.58', SystemValueNormalizer::contaoVersion('V4.13.58'));
        self::assertSame('5.7.11', SystemValueNormalizer::contaoVersion('5.7.11'));
    }

    public function testEmptyContaoVersionIsRejected(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Die Contao-Version konnte nicht ermittelt werden.');

        SystemValueNormalizer::contaoVersion('   ');
    }

    public function testPhpVersionIsReducedToMajorMinor(): void
    {
        self::assertSame('8.5', SystemValueNormalizer::phpVersion('8.5.3-nmm1'));
        self::assertSame('8.4', SystemValueNormalizer::phpVersion('8.4.12'));
        self::assertSame('8.3', SystemValueNormalizer::phpVersion(' 8.3.0 '));
    }

    public function testExactPhpVersionKeepsPatchLevel(): void
    {
        self::assertSame('8.5.3', SystemValueNormalizer::phpVersionFull('8.5.3-nmm1'));
        self::assertSame('8.4.24', SystemValueNormalizer::phpVersionFull('8.4.24'));
        self::assertSame('8.3', SystemValueNormalizer::phpVersionFull(' 8.3 '));
    }

    public function testInvalidPhpVersionIsRejected(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Die PHP-Version „unknown“ konnte nicht verarbeitet werden.');

        SystemValueNormalizer::phpVersion('unknown');
    }
}
