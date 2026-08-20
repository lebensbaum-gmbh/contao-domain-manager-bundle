<?php

declare(strict_types=1);

namespace Lebensbaum\ContaoDomainManagerBundle\Setup;

use Contao\BackendUser;
use Doctrine\DBAL\Connection;
use RuntimeException;
use Throwable;

final class SetupInstaller
{
    public function __construct(
        private readonly Connection $connection,
        private readonly SetupInspector $setupInspector,
    ) {
    }

    /**
     * @return array{created:bool,page_id:int,article_id:int|null,content_id:int|null}
     */
    public function repairMissingError403(): array
    {
        $inspection = $this->setupInspector->inspect();
        $errorPage = $this->findItem($inspection['items'], 'error_403_page');

        if (null !== $errorPage && $errorPage['found'] && null !== $errorPage['id']) {
            return [
                'created' => false,
                'page_id' => $errorPage['id'],
                'article_id' => null,
                'content_id' => null,
            ];
        }

        if (['error_403_page'] !== $inspection['missing']) {
            throw new RuntimeException(
                'Dieser Ausbauschritt kann derzeit nur eine allein fehlende 403-Seite ergänzen.'
            );
        }

        $rootPage = $this->findItem($inspection['items'], 'root_page');
        $rootPageId = (int) ($rootPage['id'] ?? 0);

        if ($rootPageId < 1) {
            throw new RuntimeException('Der Startpunkt der Domainverwaltung konnte nicht ermittelt werden.');
        }

        $timestamp = time();
        $this->connection->beginTransaction();

        try {
            $pageSorting = (int) $this->connection->fetchOne(
                'SELECT COALESCE(MAX(sorting), 0) + 128 FROM tl_page WHERE pid = ?',
                [$rootPageId]
            );

            $this->connection->insert('tl_page', [
                'pid' => $rootPageId,
                'sorting' => max(128, $pageSorting),
                'tstamp' => $timestamp,
                'title' => 'Zugriff verweigert',
                'type' => 'error_403',
                'pageTitle' => 'Zugriff verweigert',
                'robots' => 'noindex,nofollow',
                'cssClass' => 'domainverwaltung-error-page',
                'published' => 1,
            ]);
            $pageId = (int) $this->connection->lastInsertId();

            if ($pageId < 1) {
                throw new RuntimeException('Die 403-Seite konnte nicht angelegt werden.');
            }

            $this->connection->insert('tl_article', [
                'pid' => $pageId,
                'sorting' => 128,
                'tstamp' => $timestamp,
                'title' => 'Zugriff verweigert',
                'author' => $this->resolveAuthorId(),
                'inColumn' => 'main',
                'published' => 1,
            ]);
            $articleId = (int) $this->connection->lastInsertId();

            if ($articleId < 1) {
                throw new RuntimeException('Der Artikel der 403-Seite konnte nicht angelegt werden.');
            }

            $this->connection->insert('tl_content', [
                'pid' => $articleId,
                'ptable' => 'tl_article',
                'sorting' => 128,
                'tstamp' => $timestamp,
                'type' => 'text',
                'text' => '<h1>Zugriff verweigert</h1><p>Sie sind angemeldet, besitzen jedoch keine Berechtigung für die Domainverwaltung.</p>',
            ]);
            $contentId = (int) $this->connection->lastInsertId();

            if ($contentId < 1) {
                throw new RuntimeException('Der Inhalt der 403-Seite konnte nicht angelegt werden.');
            }

            $this->connection->commit();

            return [
                'created' => true,
                'page_id' => $pageId,
                'article_id' => $articleId,
                'content_id' => $contentId,
            ];
        } catch (Throwable $exception) {
            if ($this->connection->isTransactionActive()) {
                $this->connection->rollBack();
            }

            throw $exception;
        }
    }

    /**
     * @param list<array{key:string,label:string,expected:string,found:bool,id:int|null}> $items
     * @return array{key:string,label:string,expected:string,found:bool,id:int|null}|null
     */
    private function findItem(array $items, string $key): ?array
    {
        foreach ($items as $item) {
            if ($key === $item['key']) {
                return $item;
            }
        }

        return null;
    }

    private function resolveAuthorId(): int
    {
        try {
            return (int) BackendUser::getInstance()->id;
        } catch (Throwable) {
            return 0;
        }
    }
}
