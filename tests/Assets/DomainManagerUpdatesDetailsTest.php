<?php

declare(strict_types=1);

namespace Lebensbaum\ContaoDomainManagerBundle\Tests\Assets;

use PHPUnit\Framework\TestCase;

final class DomainManagerUpdatesDetailsTest extends TestCase
{
    public function testUpdatePageHasAccessiblePackagePlanAndInlineErrors(): void
    {
        $template = file_get_contents(__DIR__.'/../../contao/templates/content_element/domain_manager_updates.html.twig');

        self::assertIsString($template);
        self::assertStringContainsString('data-dm-update-plan', $template);
        self::assertStringContainsString('detail.operations', $template);
        self::assertStringContainsString('detail.error_heading', $template);
        self::assertStringContainsString('Vollständige Fehlermeldung', $template);
        self::assertStringContainsString('Downgrade', $template);
        self::assertStringContainsString('role="status"', $template);
    }

    public function testBulkActionsRefreshUpdateDetails(): void
    {
        $script = file_get_contents(__DIR__.'/../../public/js/domain-manager-updates.js');

        self::assertIsString($script);
        self::assertStringContainsString("resultRow.querySelector('[data-dm-update-extra]')", $script);
        self::assertStringContainsString('currentExtra.replaceWith(resultExtra.cloneNode(true));', $script);
        self::assertStringContainsString('currentExtra.remove();', $script);
    }
}
