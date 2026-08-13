<?php

declare(strict_types=1);

namespace Lebensbaum\ContaoDomainManagerBundle\Security\Voter\DataContainer;

use Contao\BackendUser;
use Contao\CoreBundle\Security\ContaoCorePermissions;
use Contao\CoreBundle\Security\DataContainer\CreateAction;
use Contao\CoreBundle\Security\DataContainer\DeleteAction;
use Contao\CoreBundle\Security\DataContainer\ReadAction;
use Contao\CoreBundle\Security\DataContainer\UpdateAction;
use Lebensbaum\ContaoDomainManagerBundle\Security\DomainManagerPermissions;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Authoritative voter for the Domain Manager domain table.
 *
 * A successful check deliberately returns GRANT instead of ABSTAIN. With
 * Contao's priority strategy and this voter's priority of 200 this makes the
 * bundle's own permission authoritative before the core TableAccessVoter.
 */
final class DomainAccessVoter extends Voter
{
    private const TABLE = 'tl_domain_manager_domain';
    private const MODULE = 'domain_manager_domains';

    public function __construct(
        private readonly AccessDecisionManagerInterface $accessDecisionManager,
    ) {
    }

    public function supportsAttribute(string $attribute): bool
    {
        return $attribute === ContaoCorePermissions::DC_PREFIX.self::TABLE;
    }

    public function supportsType(string $subjectType): bool
    {
        return \in_array($subjectType, [CreateAction::class, ReadAction::class, UpdateAction::class, DeleteAction::class], true);
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return $this->supportsAttribute($attribute)
            && ($subject instanceof CreateAction
                || $subject instanceof ReadAction
                || $subject instanceof UpdateAction
                || $subject instanceof DeleteAction);
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof BackendUser) {
            return false;
        }

        if ($user->isAdmin) {
            return true;
        }

        if (!$this->accessDecisionManager->decide(
            $token,
            [ContaoCorePermissions::USER_CAN_ACCESS_MODULE],
            self::MODULE,
        )) {
            return false;
        }

        if ($subject instanceof ReadAction) {
            return true;
        }

        return $this->accessDecisionManager->decide(
            $token,
            [DomainManagerPermissions::attribute()],
            DomainManagerPermissions::EDIT_RECORDS,
        );
    }
}
