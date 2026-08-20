<?php

declare(strict_types=1);

namespace Lebensbaum\ContaoDomainManagerBundle\EventListener;

use Contao\CoreBundle\Event\ContaoCoreEvents;
use Contao\CoreBundle\Event\MenuEvent;
use Lebensbaum\ContaoDomainManagerBundle\Controller\Backend\SetupController;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\RequestStack;

#[AsEventListener(ContaoCoreEvents::BACKEND_MENU_BUILD, priority: -255)]
final class BackendMenuListener
{
    public function __construct(
        private readonly RequestStack $requestStack,
    ) {
    }

    public function __invoke(MenuEvent $event): void
    {
        $tree = $event->getTree();

        if ('mainMenu' !== $tree->getName()) {
            return;
        }

        $parent = $tree->getChild('domain_manager');

        if (null === $parent || null !== $parent->getChild('domain_manager_setup')) {
            return;
        }

        $request = $this->requestStack->getCurrentRequest();
        $label = $GLOBALS['TL_LANG']['MOD']['domain_manager_setup'][0] ?? 'Ersteinrichtung';
        $title = $GLOBALS['TL_LANG']['MOD']['domain_manager_setup'][1] ?? 'Domainverwaltung automatisch einrichten und prüfen.';

        $node = $event->getFactory()
            ->createItem('domain_manager_setup', ['route' => SetupController::class])
            ->setLabel($label)
            ->setLinkAttribute('title', $title)
            ->setLinkAttribute('class', 'domain-manager-setup')
            ->setCurrent($request?->attributes->get('_controller') === SetupController::class)
        ;

        $parent->addChild($node);
    }
}
