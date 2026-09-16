<?php

declare(strict_types=1);

namespace Lebensbaum\ContaoDomainManagerBundle\Controller\FrontendModule;

use Contao\CoreBundle\Controller\FrontendModule\AbstractFrontendModuleController;
use Contao\CoreBundle\DependencyInjection\Attribute\AsFrontendModule;
use Contao\CoreBundle\Twig\FragmentTemplate;
use Contao\ModuleModel;
use Contao\PageModel;
use Lebensbaum\ContaoDomainManagerBundle\Frontend\FrontendWorkspaceNavigation;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

#[AsFrontendModule(
    type: 'domain_manager_navigation',
    category: 'domain_manager',
    template: '@Contao/frontend_module/domain_manager_navigation.html.twig',
)]
final class DomainManagerNavigationController extends AbstractFrontendModuleController
{
    public function __construct(private readonly FrontendWorkspaceNavigation $navigation)
    {
    }

    protected function getResponse(
        FragmentTemplate $template,
        ModuleModel $model,
        Request $request,
    ): Response {
        $page = $request->attributes->get('pageModel');
        $currentPageId = $page instanceof PageModel
            ? (int) $page->id
            : (is_numeric($page) ? (int) $page : 0);

        $template->set('items', $this->navigation->build($currentPageId));

        $response = $template->getResponse();
        $response->headers->set('Cache-Control', 'private, no-store, max-age=0');

        return $response;
    }
}
