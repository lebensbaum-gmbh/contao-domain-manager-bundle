<?php

declare(strict_types=1);

namespace Lebensbaum\ContaoDomainManagerBundle\Controller\ContentElement;

use Contao\ContentModel;
use Contao\CoreBundle\Controller\ContentElement\AbstractContentElementController;
use Contao\CoreBundle\DependencyInjection\Attribute\AsContentElement;
use Contao\CoreBundle\Twig\FragmentTemplate;
use Lebensbaum\ContaoDomainManagerBundle\Frontend\FrontendWorkspaceNavigation;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

#[AsContentElement(
    type: 'domain_manager_navigation',
    category: 'domain_manager',
)]
final class DomainManagerNavigationController extends AbstractContentElementController
{
    public function __construct(private readonly FrontendWorkspaceNavigation $navigation)
    {
    }

    protected function getResponse(
        FragmentTemplate $template,
        ContentModel $model,
        Request $request,
    ): Response {
        $template->set('items', $this->navigation->build((int) $model->pid));

        $response = $template->getResponse();
        $response->headers->set('Cache-Control', 'private, no-store, max-age=0');

        return $response;
    }
}
