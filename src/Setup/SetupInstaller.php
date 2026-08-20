<?php

declare(strict_types=1);

namespace Lebensbaum\ContaoDomainManagerBundle\Setup;

use Contao\BackendUser;
use Contao\Idna;
use Contao\StringUtil;
use Doctrine\DBAL\Connection;
use RuntimeException;
use Throwable;

final class SetupInstaller
{
    private const MEMBER_GROUP_NAME = 'Domainverwaltung';
    private const THEME_NAME = 'Domainverwaltung';
    private const LAYOUT_NAME = 'Domainverwaltung';
    private const LOGIN_MODULE_NAME = 'Domainverwaltung – Login';

    public function __construct(
        private readonly Connection $connection,
        private readonly SetupInspector $setupInspector,
    ) {
    }

    /**
     * @return array{created:list<string>,complete:bool}
     */
    public function completeSetup(string $hostname): array
    {
        $this->assertRequiredTables();

        $inspection = $this->setupInspector->inspect();
        if ($inspection['complete']) {
            return ['created' => [], 'complete' => true];
        }

        $hostname = $this->normalizeHostname($hostname);
        $items = $this->indexItems($inspection['items']);
        $created = [];
        $timestamp = time();

        $this->connection->beginTransaction();

        try {
            $memberGroupId = $this->existingId($items, 'member_group')
                ?? $this->createMemberGroup($timestamp, $created);

            $themeId = $this->existingId($items, 'theme')
                ?? $this->createTheme($timestamp, $created);

            $layoutId = $this->existingId($items, 'layout')
                ?? $this->createLayout($themeId, $timestamp, $created);

            $rootPageId = $this->existingId($items, 'root_page');
            if (null === $rootPageId) {
                $this->assertHostnameAvailable($hostname);
                $rootPageId = $this->createRootPage($layoutId, $hostname, $timestamp, $created);
            } else {
                $this->ensureRootLayout($rootPageId, $layoutId, $timestamp);
            }

            $overviewPageId = $this->existingId($items, 'overview_page')
                ?? $this->createOverviewPage($rootPageId, $memberGroupId, $timestamp, $created);

            $loginPageId = $this->existingId($items, 'login_page')
                ?? $this->createLoginPage($rootPageId, $timestamp, $created);

            $loginModuleId = $this->existingId($items, 'login_module')
                ?? $this->createLoginModule($themeId, $overviewPageId, $timestamp, $created);

            $this->ensureLoginModuleConfiguration($loginModuleId, $overviewPageId, $timestamp);
            $this->ensureOverviewContent($overviewPageId, $loginModuleId, $timestamp, $created);
            $this->ensureLoginContent($loginPageId, $loginModuleId, $timestamp, $created);

            $error401PageId = $this->existingId($items, 'error_401_page')
                ?? $this->createError401Page($rootPageId, $loginPageId, $timestamp, $created);
            $this->ensureError401Configuration($error401PageId, $loginPageId, $timestamp);

            $error403PageId = $this->existingId($items, 'error_403_page')
                ?? $this->createError403Page($rootPageId, $timestamp, $created);
            $this->ensureError403Content($error403PageId, $timestamp, $created);

            $this->ensureMemberGroupSettings($memberGroupId, $timestamp);

            $this->connection->commit();
        } catch (Throwable $exception) {
            if ($this->connection->getTransactionNestingLevel() > 0) {
                $this->connection->rollBack();
            }

            throw $exception;
        }

        return [
            'created' => $created,
            'complete' => $this->setupInspector->inspect()['complete'],
        ];
    }

    /**
     * Kept for the first v1.4 development step. The generic installer now
     * handles the same repair idempotently.
     *
     * @return array{created:bool,page_id:int,article_id:int|null,content_id:int|null}
     */
    public function repairMissingError403(): array
    {
        $before = $this->setupInspector->inspect();
        $errorPage = $this->findItem($before['items'], 'error_403_page');

        if (null !== $errorPage && $errorPage['found'] && null !== $errorPage['id']) {
            return [
                'created' => false,
                'page_id' => $errorPage['id'],
                'article_id' => null,
                'content_id' => null,
            ];
        }

        if (['error_403_page'] !== $before['missing']) {
            throw new RuntimeException('Dieser Reparaturschritt erwartet ausschließlich eine fehlende 403-Seite.');
        }

        $rootPageId = $this->existingId($this->indexItems($before['items']), 'root_page');
        if (null === $rootPageId) {
            throw new RuntimeException('Der Startpunkt der Domainverwaltung konnte nicht ermittelt werden.');
        }

        $created = [];
        $timestamp = time();
        $this->connection->beginTransaction();

        try {
            $pageId = $this->createError403Page($rootPageId, $timestamp, $created);
            $this->ensureError403Content($pageId, $timestamp, $created);
            $this->connection->commit();
        } catch (Throwable $exception) {
            if ($this->connection->getTransactionNestingLevel() > 0) {
                $this->connection->rollBack();
            }

            throw $exception;
        }

        $articleId = $this->findArticleId($pageId);
        $contentId = null;
        if (null !== $articleId) {
            $contentId = $this->findContentId($articleId, 'text');
        }

        return [
            'created' => true,
            'page_id' => $pageId,
            'article_id' => $articleId,
            'content_id' => $contentId,
        ];
    }

    private function createMemberGroup(int $timestamp, array &$created): int
    {
        $this->connection->insert('tl_member_group', [
            'tstamp' => $timestamp,
            'name' => self::MEMBER_GROUP_NAME,
        ]);
        $id = $this->lastInsertId('Frontend-Mitgliedergruppe');
        $created[] = 'Frontend-Mitgliedergruppe';

        return $id;
    }

    private function createTheme(int $timestamp, array &$created): int
    {
        $this->connection->insert('tl_theme', [
            'tstamp' => $timestamp,
            'name' => self::THEME_NAME,
            'author' => $this->resolveAuthorName(),
        ]);
        $id = $this->lastInsertId('Theme');
        $created[] = 'Theme';

        return $id;
    }

    private function createLayout(int $themeId, int $timestamp, array &$created): int
    {
        $this->connection->insert('tl_layout', [
            'pid' => $themeId,
            'tstamp' => $timestamp,
            'name' => self::LAYOUT_NAME,
            'type' => 'default',
            'rows' => '1rw',
            'cols' => '1cl',
            'modules' => serialize([
                ['mod' => 0, 'col' => 'main', 'enable' => 1],
            ]),
            'template' => 'fe_page',
        ]);
        $id = $this->lastInsertId('Seitenlayout');
        $created[] = 'Seitenlayout';

        return $id;
    }

    private function createRootPage(
        int $layoutId,
        string $hostname,
        int $timestamp,
        array &$created,
    ): int {
        $this->connection->insert('tl_page', [
            'pid' => 0,
            'sorting' => $this->nextSorting('tl_page', 0),
            'tstamp' => $timestamp,
            'title' => 'Domainverwaltung',
            'type' => 'root',
            'pageTitle' => 'Domainverwaltung',
            'dns' => $hostname,
            'language' => 'de',
            'fallback' => 1,
            'includeLayout' => 1,
            'layout' => $layoutId,
            'published' => 1,
        ]);
        $id = $this->lastInsertId('Startpunkt');
        $created[] = 'Startpunkt einer Website';

        return $id;
    }

    private function ensureRootLayout(int $rootPageId, int $layoutId, int $timestamp): void
    {
        $row = $this->connection->fetchAssociative(
            'SELECT layout, includeLayout FROM tl_page WHERE id = ? LIMIT 1',
            [$rootPageId]
        );

        if (false === $row) {
            throw new RuntimeException('Der Startpunkt der Domainverwaltung ist nicht mehr vorhanden.');
        }

        if ((int) $row['layout'] === $layoutId && 1 === (int) $row['includeLayout']) {
            return;
        }

        $this->connection->update('tl_page', [
            'layout' => $layoutId,
            'includeLayout' => 1,
            'tstamp' => $timestamp,
        ], ['id' => $rootPageId]);
    }

    private function createOverviewPage(
        int $rootPageId,
        int $memberGroupId,
        int $timestamp,
        array &$created,
    ): int {
        $this->connection->insert('tl_page', [
            'pid' => $rootPageId,
            'sorting' => $this->nextSorting('tl_page', $rootPageId),
            'tstamp' => $timestamp,
            'title' => 'Domainübersicht',
            'type' => 'regular',
            'alias' => 'index',
            'pageTitle' => 'Domainübersicht',
            'protected' => 1,
            'groups' => serialize([$memberGroupId]),
            'cssClass' => 'domainverwaltung-page',
            'published' => 1,
        ]);
        $id = $this->lastInsertId('Seite Domainübersicht');
        $created[] = 'Seite Domainübersicht';

        return $id;
    }

    private function createLoginPage(int $rootPageId, int $timestamp, array &$created): int
    {
        $this->connection->insert('tl_page', [
            'pid' => $rootPageId,
            'sorting' => $this->nextSorting('tl_page', $rootPageId),
            'tstamp' => $timestamp,
            'title' => 'Login',
            'type' => 'regular',
            'alias' => 'login',
            'pageTitle' => 'Domainverwaltung – Login',
            'robots' => 'noindex,nofollow',
            'cssClass' => 'domainverwaltung-login-page',
            'published' => 1,
        ]);
        $id = $this->lastInsertId('Seite Login');
        $created[] = 'Seite Login';

        return $id;
    }

    private function createLoginModule(
        int $themeId,
        int $overviewPageId,
        int $timestamp,
        array &$created,
    ): int {
        $this->connection->insert('tl_module', [
            'pid' => $themeId,
            'tstamp' => $timestamp,
            'name' => self::LOGIN_MODULE_NAME,
            'type' => 'login',
            'jumpTo' => $overviewPageId,
            'cssID' => serialize(['', 'domainverwaltung-login']),
        ]);
        $id = $this->lastInsertId('Login-Modul');
        $created[] = 'Login-Modul';

        return $id;
    }

    private function ensureLoginModuleConfiguration(
        int $loginModuleId,
        int $overviewPageId,
        int $timestamp,
    ): void {
        $this->connection->update('tl_module', [
            'jumpTo' => $overviewPageId,
            'cssID' => serialize(['', 'domainverwaltung-login']),
            'tstamp' => $timestamp,
        ], ['id' => $loginModuleId]);
    }

    private function ensureOverviewContent(
        int $pageId,
        int $loginModuleId,
        int $timestamp,
        array &$created,
    ): void {
        $articleId = $this->ensureArticle($pageId, 'Domainübersicht', $timestamp, $created);

        $this->ensureHeadline($articleId, 'Domainübersicht', 128, $timestamp, $created);
        $this->ensureModuleElement($articleId, $loginModuleId, 256, $timestamp, $created);
        $this->ensureSimpleContentElement($articleId, 'domain_manager_filter', 384, $timestamp, 'Inhaltselement Domainfilter', $created);
        $this->ensureSimpleContentElement($articleId, 'domain_manager_overview', 512, $timestamp, 'Inhaltselement Domainübersicht', $created);
    }

    private function ensureLoginContent(
        int $pageId,
        int $loginModuleId,
        int $timestamp,
        array &$created,
    ): void {
        $articleId = $this->ensureArticle($pageId, 'Login', $timestamp, $created);
        $this->ensureHeadline($articleId, 'Domainverwaltung', 128, $timestamp, $created);
        $this->ensureModuleElement($articleId, $loginModuleId, 256, $timestamp, $created);
    }

    private function createError401Page(
        int $rootPageId,
        int $loginPageId,
        int $timestamp,
        array &$created,
    ): int {
        $this->connection->insert('tl_page', [
            'pid' => $rootPageId,
            'sorting' => $this->nextSorting('tl_page', $rootPageId),
            'tstamp' => $timestamp,
            'title' => 'Nicht authentifiziert',
            'type' => 'error_401',
            'pageTitle' => 'Anmeldung erforderlich',
            'robots' => 'noindex,nofollow',
            'autoforward' => 1,
            'jumpTo' => $loginPageId,
            'published' => 1,
        ]);
        $id = $this->lastInsertId('401-Seite');
        $created[] = '401 – Nicht authentifiziert';

        return $id;
    }

    private function ensureError401Configuration(int $pageId, int $loginPageId, int $timestamp): void
    {
        $this->connection->update('tl_page', [
            'autoforward' => 1,
            'jumpTo' => $loginPageId,
            'published' => 1,
            'tstamp' => $timestamp,
        ], ['id' => $pageId]);
    }

    private function createError403Page(int $rootPageId, int $timestamp, array &$created): int
    {
        $this->connection->insert('tl_page', [
            'pid' => $rootPageId,
            'sorting' => $this->nextSorting('tl_page', $rootPageId),
            'tstamp' => $timestamp,
            'title' => 'Zugriff verweigert',
            'type' => 'error_403',
            'pageTitle' => 'Zugriff verweigert',
            'robots' => 'noindex,nofollow',
            'cssClass' => 'domainverwaltung-error-page',
            'published' => 1,
        ]);
        $id = $this->lastInsertId('403-Seite');
        $created[] = '403 – Zugriff verweigert';

        return $id;
    }

    private function ensureError403Content(int $pageId, int $timestamp, array &$created): void
    {
        $articleId = $this->findArticleId($pageId);
        if (null !== $articleId) {
            return;
        }

        $articleId = $this->createArticle($pageId, 'Zugriff verweigert', $timestamp);
        $this->connection->insert('tl_content', [
            'pid' => $articleId,
            'ptable' => 'tl_article',
            'sorting' => 128,
            'tstamp' => $timestamp,
            'type' => 'text',
            'text' => '<h1>Zugriff verweigert</h1><p>Sie sind angemeldet, besitzen jedoch keine Berechtigung für die Domainverwaltung.</p>',
        ]);
        $this->lastInsertId('Inhalt der 403-Seite');
        $created[] = 'Inhalt der 403-Seite';
    }

    private function ensureArticle(int $pageId, string $title, int $timestamp, array &$created): int
    {
        $articleId = $this->findArticleId($pageId);
        if (null !== $articleId) {
            return $articleId;
        }

        $articleId = $this->createArticle($pageId, $title, $timestamp);
        $created[] = sprintf('Artikel „%s“', $title);

        return $articleId;
    }

    private function createArticle(int $pageId, string $title, int $timestamp): int
    {
        $this->connection->insert('tl_article', [
            'pid' => $pageId,
            'sorting' => $this->nextSorting('tl_article', $pageId),
            'tstamp' => $timestamp,
            'title' => $title,
            'author' => $this->resolveAuthorId(),
            'inColumn' => 'main',
            'published' => 1,
        ]);

        return $this->lastInsertId('Artikel');
    }

    private function ensureHeadline(
        int $articleId,
        string $headline,
        int $sorting,
        int $timestamp,
        array &$created,
    ): void {
        if (null !== $this->findContentId($articleId, 'headline')) {
            return;
        }

        $this->connection->insert('tl_content', [
            'pid' => $articleId,
            'ptable' => 'tl_article',
            'sorting' => $sorting,
            'tstamp' => $timestamp,
            'type' => 'headline',
            'headline' => serialize(['value' => $headline, 'unit' => 'h1']),
        ]);
        $this->lastInsertId('Überschrift');
        $created[] = sprintf('Überschrift „%s“', $headline);
    }

    private function ensureModuleElement(
        int $articleId,
        int $moduleId,
        int $sorting,
        int $timestamp,
        array &$created,
    ): void {
        $existing = $this->connection->fetchOne(
            "SELECT id FROM tl_content
             WHERE pid = ? AND ptable = 'tl_article' AND type = 'module' AND module = ?
             ORDER BY id LIMIT 1",
            [$articleId, $moduleId]
        );

        if (false !== $existing && (int) $existing > 0) {
            return;
        }

        $this->connection->insert('tl_content', [
            'pid' => $articleId,
            'ptable' => 'tl_article',
            'sorting' => $sorting,
            'tstamp' => $timestamp,
            'type' => 'module',
            'module' => $moduleId,
        ]);
        $this->lastInsertId('Login-Inhaltselement');
        $created[] = 'Login-Inhaltselement';
    }

    private function ensureSimpleContentElement(
        int $articleId,
        string $type,
        int $sorting,
        int $timestamp,
        string $label,
        array &$created,
    ): void {
        if (null !== $this->findContentId($articleId, $type)) {
            return;
        }

        $this->connection->insert('tl_content', [
            'pid' => $articleId,
            'ptable' => 'tl_article',
            'sorting' => $sorting,
            'tstamp' => $timestamp,
            'type' => $type,
        ]);
        $this->lastInsertId($label);
        $created[] = $label;
    }

    private function ensureMemberGroupSettings(int $memberGroupId, int $timestamp): void
    {
        $row = $this->connection->fetchAssociative(
            'SELECT id, sync_member_groups FROM tl_domain_manager_settings WHERE id = 1 LIMIT 1'
        );

        if (false === $row) {
            $this->connection->insert('tl_domain_manager_settings', [
                'id' => 1,
                'tstamp' => $timestamp,
                'sync_member_groups' => serialize([$memberGroupId]),
                'stale_sync_days' => 30,
            ]);

            return;
        }

        $groups = [];
        foreach (StringUtil::deserialize($row['sync_member_groups'] ?? null, true) as $groupId) {
            if (is_numeric($groupId) && (int) $groupId > 0) {
                $groups[(int) $groupId] = (int) $groupId;
            }
        }
        $groups[$memberGroupId] = $memberGroupId;

        $this->connection->update('tl_domain_manager_settings', [
            'sync_member_groups' => serialize(array_values($groups)),
            'tstamp' => $timestamp,
        ], ['id' => 1]);
    }

    private function assertRequiredTables(): void
    {
        $required = [
            'tl_member_group',
            'tl_theme',
            'tl_layout',
            'tl_page',
            'tl_module',
            'tl_article',
            'tl_content',
            'tl_domain_manager_settings',
        ];
        $schemaManager = $this->connection->createSchemaManager();

        foreach ($required as $table) {
            if (!$schemaManager->tablesExist([$table])) {
                throw new RuntimeException(sprintf(
                    'Die Tabelle „%s“ fehlt. Bitte zuerst die Contao-Datenbankmigration ausführen.',
                    $table
                ));
            }
        }
    }

    private function assertHostnameAvailable(string $hostname): void
    {
        $existing = $this->connection->fetchOne(
            "SELECT id FROM tl_page WHERE type = 'root' AND dns = ? ORDER BY id LIMIT 1",
            [$hostname]
        );

        if (false !== $existing && (int) $existing > 0) {
            throw new RuntimeException(sprintf(
                'Der Hostname „%s“ wird bereits von einem anderen Startpunkt verwendet. Bitte für die Domainverwaltung eine eigene (Sub-)Domain verwenden.',
                $hostname
            ));
        }
    }

    private function normalizeHostname(string $hostname): string
    {
        $hostname = strtolower(trim($hostname));
        $hostname = preg_replace('~^https?://~i', '', $hostname) ?? $hostname;
        $hostname = explode('/', $hostname, 2)[0];
        $hostname = explode(':', $hostname, 2)[0];
        $hostname = Idna::encode($hostname);

        if (
            '' === $hostname
            || strlen($hostname) > 255
            || 1 !== preg_match('/^[a-z0-9.-]+$/i', $hostname)
            || str_starts_with($hostname, '.')
            || str_ends_with($hostname, '.')
        ) {
            throw new RuntimeException('Bitte einen gültigen Hostnamen für die Domainverwaltung angeben.');
        }

        return $hostname;
    }

    private function nextSorting(string $table, int $pid): int
    {
        $sorting = (int) $this->connection->fetchOne(
            sprintf('SELECT COALESCE(MAX(sorting), 0) + 128 FROM `%s` WHERE pid = ?', $table),
            [$pid]
        );

        return max(128, $sorting);
    }

    private function lastInsertId(string $label): int
    {
        $id = (int) $this->connection->lastInsertId();
        if ($id < 1) {
            throw new RuntimeException(sprintf('„%s“ konnte nicht angelegt werden.', $label));
        }

        return $id;
    }

    private function findArticleId(int $pageId): ?int
    {
        $id = $this->connection->fetchOne(
            'SELECT id FROM tl_article WHERE pid = ? ORDER BY sorting, id LIMIT 1',
            [$pageId]
        );

        return false === $id || (int) $id < 1 ? null : (int) $id;
    }

    private function findContentId(int $articleId, string $type): ?int
    {
        $id = $this->connection->fetchOne(
            "SELECT id FROM tl_content
             WHERE pid = ? AND ptable = 'tl_article' AND type = ?
             ORDER BY sorting, id LIMIT 1",
            [$articleId, $type]
        );

        return false === $id || (int) $id < 1 ? null : (int) $id;
    }

    /**
     * @param list<array{key:string,label:string,expected:string,found:bool,id:int|null}> $items
     * @return array<string,array{key:string,label:string,expected:string,found:bool,id:int|null}>
     */
    private function indexItems(array $items): array
    {
        $indexed = [];
        foreach ($items as $item) {
            $indexed[$item['key']] = $item;
        }

        return $indexed;
    }

    /**
     * @param array<string,array{key:string,label:string,expected:string,found:bool,id:int|null}> $items
     */
    private function existingId(array $items, string $key): ?int
    {
        $item = $items[$key] ?? null;
        if (null === $item || !$item['found'] || null === $item['id']) {
            return null;
        }

        return $item['id'];
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

    private function resolveAuthorName(): string
    {
        try {
            $user = BackendUser::getInstance();
            $name = trim((string) $user->name);

            return '' !== $name ? $name : 'Contao Domain Manager';
        } catch (Throwable) {
            return 'Contao Domain Manager';
        }
    }
}
