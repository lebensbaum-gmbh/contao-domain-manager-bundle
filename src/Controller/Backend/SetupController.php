<?php

declare(strict_types=1);

namespace Lebensbaum\ContaoDomainManagerBundle\Controller\Backend;

use Contao\CoreBundle\Controller\AbstractBackendController;
use Lebensbaum\ContaoDomainManagerBundle\Setup\SetupInspector;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

#[AsController]
#[Route(
    '%contao.backend.route_prefix%/domain-manager/setup',
    name: self::class,
    defaults: ['_scope' => 'backend'],
    methods: ['GET'],
)]
final class SetupController extends AbstractBackendController
{
    public function __construct(
        private readonly SetupInspector $setupInspector,
        private readonly AuthorizationCheckerInterface $authorizationChecker,
    ) {
    }

    public function __invoke(): Response
    {
        if (!$this->authorizationChecker->isGranted('ROLE_ADMIN')) {
            throw new AccessDeniedHttpException('Die Ersteinrichtung steht nur Administratoren zur Verfügung.');
        }

        return $this->render('backend/setup.html.twig', [
            'title' => 'Domain Manager – Ersteinrichtung',
            'headline' => 'Ersteinrichtung',
            'setup' => $this->setupInspector->inspect(),
        ]);
    }
}
