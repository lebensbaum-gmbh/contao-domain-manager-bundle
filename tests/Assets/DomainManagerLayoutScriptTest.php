<?php

declare(strict_types=1);

namespace Lebensbaum\ContaoDomainManagerBundle\Tests\Assets;

use PHPUnit\Framework\TestCase;

final class DomainManagerLayoutScriptTest extends TestCase
{
    public function testSharedArticleLayoutUsesSiblingHostsInsteadOfNestedInsertBefore(): void
    {
        $script = file_get_contents(__DIR__.'/../../public/js/domain-manager-layout.js');

        self::assertIsString($script);
        self::assertStringContainsString('const hosts = findSiblingHosts(filter, overview);', $script);
        self::assertStringContainsString('hosts.parent.insertBefore(layout, filterComesFirst ? filterHost : overviewHost);', $script);
        self::assertStringNotContainsString('filterArticle.insertBefore(layout, filter);', $script);
    }
}
