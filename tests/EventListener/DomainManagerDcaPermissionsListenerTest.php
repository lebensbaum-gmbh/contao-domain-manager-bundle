<?php

declare(strict_types=1);

namespace Lebensbaum\ContaoDomainManagerBundle\Tests\EventListener;

use Contao\CoreBundle\Routing\ScopeMatcher;
use Lebensbaum\ContaoDomainManagerBundle\EventListener\DomainManagerDcaPermissionsListener;
use Lebensbaum\ContaoDomainManagerBundle\Security\BackendPermissionChecker;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

final class DomainManagerDcaPermissionsListenerTest extends TestCase
{
    private const TABLE = 'tl_domain_manager_installation';

    protected function tearDown(): void
    {
        unset($GLOBALS['TL_DCA'][self::TABLE]);
    }

    public function testSkipsPermissionChecksWithoutCurrentRequest(): void
    {
        $authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $authorizationChecker->expects(self::never())->method('isGranted');

        $scopeMatcher = $this->createMock(ScopeMatcher::class);
        $scopeMatcher->expects(self::never())->method('isBackendRequest');

        $requestStack = new RequestStack();
        $listener = new DomainManagerDcaPermissionsListener(
            new BackendPermissionChecker($authorizationChecker),
            $requestStack,
            $scopeMatcher,
        );

        $GLOBALS['TL_DCA'][self::TABLE] = $this->createDcaFixture();
        $before = $GLOBALS['TL_DCA'][self::TABLE];

        $listener(self::TABLE);

        self::assertSame($before, $GLOBALS['TL_DCA'][self::TABLE]);
    }

    public function testSkipsPermissionChecksOutsideBackendScope(): void
    {
        $authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $authorizationChecker->expects(self::never())->method('isGranted');

        $scopeMatcher = $this->createMock(ScopeMatcher::class);
        $scopeMatcher
            ->expects(self::once())
            ->method('isBackendRequest')
            ->willReturn(false);

        $requestStack = new RequestStack();
        $requestStack->push(Request::create('https://example.test/'));

        $listener = new DomainManagerDcaPermissionsListener(
            new BackendPermissionChecker($authorizationChecker),
            $requestStack,
            $scopeMatcher,
        );

        $GLOBALS['TL_DCA'][self::TABLE] = $this->createDcaFixture();
        $before = $GLOBALS['TL_DCA'][self::TABLE];

        $listener(self::TABLE);

        self::assertSame($before, $GLOBALS['TL_DCA'][self::TABLE]);
    }

    /**
     * @return array<string, mixed>
     */
    private function createDcaFixture(): array
    {
        return [
            'config' => [],
            'list' => [
                'global_operations' => [
                    'dm_new' => ['label' => 'new'],
                    'dm_all' => ['label' => 'all'],
                ],
                'operations' => [
                    'dm_edit' => ['label' => 'edit'],
                    'connection' => ['label' => 'connection'],
                ],
            ],
        ];
    }
}
