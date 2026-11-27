<?php

declare(strict_types=1);

namespace Lebensbaum\ContaoDomainManagerBundle\Tests\Frontend;

use Lebensbaum\ContaoDomainManagerBundle\Frontend\WorkspacePresentation;
use PHPUnit\Framework\TestCase;

final class WorkspacePresentationTest extends TestCase
{
    public function testFreeDoesNotAdvertiseOperationalAccess(): void
    {
        $features = (new WorkspacePresentation())->features([]);
        self::assertFalse($features['pro_available']);
        self::assertFalse($features['pro_enabled']);
        self::assertSame('https://maintend.de/', $features['pro_url']);
    }

    public function testInstalledProWithoutOperatorRightsRemainsLocked(): void
    {
        $features = (new WorkspacePresentation())->features(['pro_available' => true, 'pro_authorized' => false]);
        self::assertTrue($features['pro_available']);
        self::assertFalse($features['pro_enabled']);
    }

    public function testUnsafeSubscriptionUrlCannotBecomeALink(): void
    {
        self::assertSame('https://maintend.de/', (new WorkspacePresentation('javascript:alert(1)'))->features([])['pro_url']);
        self::assertSame('https://maintend.de/preise', (new WorkspacePresentation('https://maintend.de/preise'))->features([])['pro_url']);
    }

    public function testEightyInstallationsAreCountedWithoutDoubleCountingAttention(): void
    {
        $rows = array_fill(0, 80, ['update_state' => 'current', 'health' => ['status' => 'ok']]);
        for ($i = 0; $i < 12; ++$i) {
            $rows[$i]['update_state'] = 'available';
        }
        $rows[0]['health']['status'] = 'error';
        $rows[12]['update_state'] = 'error';
        $rows[13]['update_state'] = 'check_failed';
        $counts = (new WorkspacePresentation())->summarize($rows);
        self::assertSame(80, $counts['total']);
        self::assertSame(12, $counts['updates']);
        self::assertSame(3, $counts['problems']);
        self::assertSame(14, $counts['attention']);
    }

    public function testAnOlderSuccessfulCheckDoesNotHideNewReleaseBacklog(): void
    {
        $counts = (new WorkspacePresentation())->summarize([
            ['update_state' => 'current', 'update_release_gap' => 5],
            ['update_state' => 'current', 'update_release_gap' => 4],
        ]);
        self::assertSame(1, $counts['attention']);
        self::assertSame(0, $counts['updates']);
        self::assertSame(1, $counts['problems']);
    }
}
