<?php

declare(strict_types=1);

namespace Lebensbaum\ContaoDomainManagerBundle\Frontend;

use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Routing\ContentUrlGenerator;
use Contao\PageModel;
use Doctrine\DBAL\Connection;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Throwable;

final class FrontendWorkspaceNavigation
{
    private const ITEMS = [
        'overview' => [
            'label' => 'Domainübersicht',
            'content_type' => 'domain_manager_overview',
        ],
        'updates' => [
            'label' => 'Updates',
            'content_type' => 'domain_manager_updates',
        ],
    ];

    public function __construct(
        private readonly Connection $connection,
        private readonly ContaoFramework $framework,
        private readonly ContentUrlGenerator $urlGenerator,
    ) {
    }

    /**
     * @return list<array{key:string,label:string,url:string,active:bool}>
     */
    public function build(int $articleId): array
    {
        $currentPageId = $this->findCurrentPageId($articleId);
        $items = [];

        foreach (self::ITEMS as $key => $configuration) {
            $pageId = $this->findPageIdForContentType($configuration['content_type']);

            if ($pageId < 1) {
                continue;
            }

            try {
                $page = $this->framework->getAdapter(PageModel::class)->findByPk($pageId);

                if (!$page instanceof PageModel) {
                    continue;
                }

                $url = $this->urlGenerator->generate(
                    $page,
                    [],
                    UrlGeneratorInterface::ABSOLUTE_PATH
                );
            } catch (Throwable) {
                continue;
            }

            $items[] = [
                'key' => $key,
                'label' => $configuration['label'],
                'url' => $url,
                'active' => $pageId === $currentPageId,
            ];
        }

        return $items;
    }

    private function findCurrentPageId(int $articleId): int
    {
        if ($articleId < 1) {
            return 0;
        }

        try {
            $pageId = $this->connection->fetchOne(
                'SELECT pid FROM tl_article WHERE id = ? LIMIT 1',
                [$articleId]
            );

            return is_numeric($pageId) ? (int) $pageId : 0;
        } catch (Throwable) {
            return 0;
        }
    }

    private function findPageIdForContentType(string $contentType): int
    {
        try {
            $pageId = $this->connection->fetchOne(
                "SELECT p.id\n"
                ."FROM tl_content c\n"
                ."INNER JOIN tl_article a ON a.id = c.pid\n"
                ."INNER JOIN tl_page p ON p.id = a.pid\n"
                ."WHERE c.ptable = 'tl_article' AND c.type = ? AND p.type = 'regular'\n"
                ."ORDER BY p.id ASC\n"
                ."LIMIT 1",
                [$contentType]
            );

            return is_numeric($pageId) ? (int) $pageId : 0;
        } catch (Throwable) {
            return 0;
        }
    }
}
