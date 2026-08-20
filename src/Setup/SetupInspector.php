<?php

declare(strict_types=1);

namespace Lebensbaum\ContaoDomainManagerBundle\Setup;

use Doctrine\DBAL\Connection;
use Throwable;

final class SetupInspector
{
    public function __construct(
        private readonly Connection $connection,
    ) {
    }

    /**
     * @return array{
     *     complete: bool,
     *     found: int,
     *     total: int,
     *     missing: list<string>,
     *     items: list<array{key:string,label:string,expected:string,found:bool,id:int|null}>
     * }
     */
    public function inspect(): array
    {
        $memberGroupId = $this->findId(
            'SELECT id FROM tl_member_group WHERE name = ? ORDER BY id LIMIT 1',
            ['Domainverwaltung']
        );

        $overviewPageId = $this->findId(
            "SELECT p.id
             FROM tl_page p
             INNER JOIN tl_article a ON a.pid = p.id
             INNER JOIN tl_content c ON c.pid = a.id AND c.ptable = 'tl_article'
             WHERE p.type = 'regular' AND c.type = 'domain_manager_overview'
             ORDER BY p.id LIMIT 1"
        );

        if (null === $overviewPageId) {
            $overviewPageId = $this->findId(
                "SELECT id FROM tl_page
                 WHERE type = 'regular'
                   AND alias = 'index'
                   AND cssClass LIKE ?
                 ORDER BY id LIMIT 1",
                ['%domainverwaltung-page%']
            );
        }

        $rootPageId = null;
        if (null !== $overviewPageId) {
            $rootPageId = $this->findId(
                "SELECT root.id
                 FROM tl_page overview
                 INNER JOIN tl_page root ON root.id = overview.pid AND root.type = 'root'
                 WHERE overview.id = ?
                 LIMIT 1",
                [$overviewPageId]
            );
        }

        if (null === $rootPageId) {
            $rootPageId = $this->findId(
                "SELECT id FROM tl_page
                 WHERE type = 'root' AND title = ?
                 ORDER BY id LIMIT 1",
                ['Domainverwaltung']
            );
        }

        $layoutId = null;
        if (null !== $rootPageId) {
            $layoutId = $this->findId(
                'SELECT layout FROM tl_page WHERE id = ? AND layout > 0 LIMIT 1',
                [$rootPageId]
            );
        }

        $themeId = null;
        if (null !== $layoutId) {
            $themeId = $this->findId(
                'SELECT pid FROM tl_layout WHERE id = ? AND pid > 0 LIMIT 1',
                [$layoutId]
            );
        }

        if (null === $themeId) {
            $themeId = $this->findId(
                'SELECT id FROM tl_theme WHERE name = ? ORDER BY id LIMIT 1',
                ['Domainverwaltung']
            );
        }

        if (null === $layoutId && null !== $themeId) {
            $layoutId = $this->findId(
                'SELECT id FROM tl_layout WHERE pid = ? AND name = ? ORDER BY id LIMIT 1',
                [$themeId, 'Domainverwaltung']
            );
        }

        $loginPageId = null;
        if (null !== $rootPageId) {
            $loginPageId = $this->findId(
                "SELECT id FROM tl_page
                 WHERE pid = ? AND type = 'regular'
                   AND (alias = 'login' OR cssClass LIKE ?)
                 ORDER BY id LIMIT 1",
                [$rootPageId, '%domainverwaltung-login-page%']
            );
        }

        if (null === $loginPageId) {
            $loginPageId = $this->findId(
                "SELECT id FROM tl_page
                 WHERE type = 'regular' AND cssClass LIKE ?
                 ORDER BY id LIMIT 1",
                ['%domainverwaltung-login-page%']
            );
        }

        $error401PageId = null;
        $error403PageId = null;
        if (null !== $rootPageId) {
            $error401PageId = $this->findId(
                "SELECT id FROM tl_page WHERE pid = ? AND type = 'error_401' ORDER BY id LIMIT 1",
                [$rootPageId]
            );
            $error403PageId = $this->findId(
                "SELECT id FROM tl_page WHERE pid = ? AND type = 'error_403' ORDER BY id LIMIT 1",
                [$rootPageId]
            );
        }

        $loginModuleId = null;
        if (null !== $loginPageId) {
            $loginModuleId = $this->findId(
                "SELECT m.id
                 FROM tl_article a
                 INNER JOIN tl_content c ON c.pid = a.id AND c.ptable = 'tl_article' AND c.type = 'module'
                 INNER JOIN tl_module m ON m.id = c.module AND m.type = 'login'
                 WHERE a.pid = ?
                 ORDER BY c.id LIMIT 1",
                [$loginPageId]
            );
        }

        if (null === $loginModuleId && null !== $themeId) {
            $loginModuleId = $this->findId(
                "SELECT id FROM tl_module
                 WHERE pid = ? AND type = 'login' AND name = ?
                 ORDER BY id LIMIT 1",
                [$themeId, 'Domainverwaltung – Login']
            );
        }

        $filterElementId = null;
        $overviewElementId = null;
        if (null !== $overviewPageId) {
            $filterElementId = $this->findId(
                "SELECT c.id
                 FROM tl_content c
                 INNER JOIN tl_article a ON a.id = c.pid AND c.ptable = 'tl_article'
                 WHERE a.pid = ? AND c.type = 'domain_manager_filter'
                 ORDER BY c.id LIMIT 1",
                [$overviewPageId]
            );
            $overviewElementId = $this->findId(
                "SELECT c.id
                 FROM tl_content c
                 INNER JOIN tl_article a ON a.id = c.pid AND c.ptable = 'tl_article'
                 WHERE a.pid = ? AND c.type = 'domain_manager_overview'
                 ORDER BY c.id LIMIT 1",
                [$overviewPageId]
            );
        }

        $items = [
            $this->resultItem('member_group', 'Frontend-Mitgliedergruppe', 'Domainverwaltung', $memberGroupId),
            $this->resultItem('theme', 'Theme', 'Theme der Domainverwaltung', $themeId),
            $this->resultItem('layout', 'Seitenlayout', 'Seitenlayout der Domainverwaltung', $layoutId),
            $this->resultItem('root_page', 'Startpunkt einer Website', 'Startpunkt der Domainverwaltung', $rootPageId),
            $this->resultItem('overview_page', 'Seite Domainübersicht', 'Alias index', $overviewPageId),
            $this->resultItem('login_page', 'Seite Login', 'Alias login', $loginPageId),
            $this->resultItem('error_401_page', '401 – Nicht authentifiziert', 'Seitentyp 401 unter demselben Startpunkt', $error401PageId),
            $this->resultItem('error_403_page', '403 – Zugriff verweigert', 'Seitentyp 403 unter demselben Startpunkt', $error403PageId),
            $this->resultItem('login_module', 'Login-Modul', 'Login-Formular der Domainverwaltung', $loginModuleId),
            $this->resultItem('filter_element', 'Inhaltselement Domainfilter', 'Domainfilter auf der Übersichtsseite', $filterElementId),
            $this->resultItem('overview_element', 'Inhaltselement Domainübersicht', 'Domainübersicht auf der Übersichtsseite', $overviewElementId),
        ];

        $missing = array_values(array_map(
            static fn (array $item): string => $item['key'],
            array_filter(
                $items,
                static fn (array $item): bool => !$item['found']
            )
        ));
        $found = count($items) - count($missing);
        $total = count($items);

        return [
            'complete' => [] === $missing,
            'found' => $found,
            'total' => $total,
            'missing' => $missing,
            'items' => $items,
        ];
    }

    /** @return array{key:string,label:string,expected:string,found:bool,id:int|null} */
    private function resultItem(
        string $key,
        string $label,
        string $expected,
        ?int $id,
    ): array {
        return [
            'key' => $key,
            'label' => $label,
            'expected' => $expected,
            'found' => null !== $id,
            'id' => $id,
        ];
    }

    /** @param list<mixed> $parameters */
    private function findId(string $sql, array $parameters = []): ?int
    {
        try {
            $id = $this->connection->fetchOne($sql, $parameters);
        } catch (Throwable) {
            return null;
        }

        if (false === $id || null === $id || '' === (string) $id) {
            return null;
        }

        $id = (int) $id;

        return $id > 0 ? $id : null;
    }
}
