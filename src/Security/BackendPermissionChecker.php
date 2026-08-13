<?php

declare(strict_types=1);

namespace Lebensbaum\ContaoDomainManagerBundle\Security;

use Contao\CoreBundle\Security\ContaoCorePermissions;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

final class BackendPermissionChecker
{
    public function __construct(
        private readonly AuthorizationCheckerInterface $authorizationChecker,
    ) {
    }

    public function isGranted(string $permission): bool
    {
        return $this->authorizationChecker->isGranted('ROLE_ADMIN')
            || $this->authorizationChecker->isGranted(DomainManagerPermissions::attribute(), $permission);
    }

    public function canAccessDomainsModule(): bool
    {
        return $this->authorizationChecker->isGranted('ROLE_ADMIN')
            || $this->authorizationChecker->isGranted(
                ContaoCorePermissions::USER_CAN_ACCESS_MODULE,
                'domain_manager_domains'
            );
    }
}
