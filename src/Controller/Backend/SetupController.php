<?php

declare(strict_types=1);

namespace Lebensbaum\ContaoDomainManagerBundle\Controller\Backend;

use Contao\CoreBundle\Controller\AbstractBackendController;
use Lebensbaum\ContaoDomainManagerBundle\Setup\SetupInspector;
use Lebensbaum\ContaoDomainManagerBundle\Setup\SetupInstaller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Throwable;

#[AsController]
#[Route(
    '%contao.backend.route_prefix%/domain-manager/setup',
    name: self::class,
    defaults: ['_scope' => 'backend'],
    methods: ['GET', 'POST'],
)]
final class SetupController extends AbstractBackendController
{
    public function __construct(
        private readonly SetupInspector $setupInspector,
        private readonly SetupInstaller $setupInstaller,
        private readonly AuthorizationCheckerInterface $authorizationChecker,
    ) {
    }

    public function __invoke(Request $request): Response
    {
        if (!$this->authorizationChecker->isGranted('ROLE_ADMIN')) {
            throw new AccessDeniedHttpException('Die Ersteinrichtung steht nur Administratoren zur Verfügung.');
        }

        if ($request->isMethod('POST')) {
            $flashBag = $request->getSession()->getFlashBag();
            $action = trim((string) $request->request->get('dm_setup_action', ''));

            if ('repair_missing' !== $action) {
                $flashBag->add('domain_manager_setup_error', 'Unbekannte Aktion.');
            } else {
                try {
                    $result = $this->setupInstaller->repairMissingError403();

                    $flashBag->add(
                        'domain_manager_setup_success',
                        $result['created']
                            ? 'Die fehlende 403-Seite wurde einschließlich Artikel und Inhalt angelegt.'
                            : 'Die 403-Seite war bereits vorhanden; es wurden keine Duplikate erzeugt.'
                    );
                } catch (Throwable $exception) {
                    $flashBag->add('domain_manager_setup_error', $exception->getMessage());
                }
            }

            return new RedirectResponse(
                $request->getUriForPath($request->getPathInfo()),
                Response::HTTP_SEE_OTHER
            );
        }

        $setup = $this->setupInspector->inspect();
        $successMessages = $request->getSession()->getFlashBag()->get('domain_manager_setup_success');
        $errorMessages = $request->getSession()->getFlashBag()->get('domain_manager_setup_error');

        return $this->render('@ContaoDomainManager/backend/setup.html.twig', [
            'title' => 'Domain Manager – Ersteinrichtung',
            'headline' => 'Ersteinrichtung',
            'setup' => $setup,
            'repairable' => ['error_403_page'] === $setup['missing'],
            'success' => $successMessages[0] ?? null,
            'error' => $errorMessages[0] ?? null,
        ]);
    }
}
