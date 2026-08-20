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

            if ('complete_setup' !== $action) {
                $flashBag->add('domain_manager_setup_error', 'Unbekannte Aktion.');
            } else {
                try {
                    $hostname = trim((string) $request->request->get('dm_setup_hostname', $request->getHost()));
                    $result = $this->setupInstaller->completeSetup($hostname);

                    if ([] === $result['created']) {
                        $flashBag->add(
                            'domain_manager_setup_success',
                            'Die empfohlene Grundstruktur war bereits vollständig. Es wurden keine Duplikate erzeugt.'
                        );
                    } else {
                        $message = sprintf(
                            '%d Baustein%s wurden ergänzt.',
                            count($result['created']),
                            1 === count($result['created']) ? '' : 'e'
                        );

                        if ($result['complete']) {
                            $message .= ' Die empfohlene Grundstruktur ist jetzt vollständig.';
                        }

                        $flashBag->add('domain_manager_setup_success', $message);
                    }
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
            'default_hostname' => $request->getHost(),
            'needs_hostname' => in_array('root_page', $setup['missing'], true),
            'success' => $successMessages[0] ?? null,
            'error' => $errorMessages[0] ?? null,
        ]);
    }
}
