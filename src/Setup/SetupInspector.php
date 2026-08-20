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
     *     items: list<array{key:string,label:string,expected:string,found:bool,id:int|null}>
     * }
     */
    public function inspect(): array
    {
        $items = [
            $this->item(
                'member_group',
                'Frontend-Mitgliedergruppe',
                'Domainverwaltung',
                'SELECT id FROM tl_member_group WHERE name = ? ORDER BY id LIMIT 1',
                ['Domainverwaltung']
            ),
            $this->item(
                'theme',
                'Theme',
                'Theme des zugewiesenen Seitenlayouts',
                "SELECT t.id
                 FROM tl_page overview
                 INNER JOIN tl_page root ON root.id = overview.pid AND root.type = 'root'
                 INNER JOIN tl_layout l ON l.id = root.layout
                 INNER JOIN tl_theme t ON t.id = l.pid
                 WHERE overview.type = 'regular' AND overview.alias = 'index'
                 ORDER BY overview.id LIMIT 1"
            ),
            $this->item(
                'layout',
                'Seitenlayout',
                'Am Startpunkt zugewiesenes Seitenlayout',
                "SELECT l.id
                 FROM tl_page overview
                 INNER JOIN tl_page root ON root.id = overview.pid AND root.type = 'root'
                 INNER JOIN tl_layout l ON l.id = root.layout
                 WHERE overview.type = 'regular' AND overview.alias = 'index'
                 ORDER BY overview.id LIMIT 1"
            ),
            $this->item(
                'root_page',
                'Startpunkt einer Website',
                'Startpunkt der Domainübersicht',
                "SELECT root.id
                 FROM tl_page overview
                 INNER JOIN tl_page root ON root.id = overview.pid AND root.type = 'root'
                 WHERE overview.type = 'regular' AND overview.alias = 'index'
                 ORDER BY overview.id LIMIT 1"
            ),
            $this->item(
                'overview_page',
                'Seite Domainübersicht',
                'Alias index',
                "SELECT id FROM tl_page WHERE type = 'regular' AND alias = 'index' ORDER BY id LIMIT 1"
            ),
            $this->item(
                'login_page',
                'Seite Login',
                'Alias login',
                "SELECT id FROM tl_page WHERE type = 'regular' AND alias = 'login' ORDER BY id LIMIT 1"
            ),
            $this->item(
                'error_401_page',
                '401 – Nicht authentifiziert',
                'Seitentyp 401 unter demselben Startpunkt',
                "SELECT error_page.id
                 FROM tl_page overview
                 INNER JOIN tl_page root ON root.id = overview.pid AND root.type = 'root'
                 INNER JOIN tl_page error_page ON error_page.pid = root.id AND error_page.type = 'error_401'
                 WHERE overview.type = 'regular' AND overview.alias = 'index'
                 ORDER BY error_page.id LIMIT 1"
            ),
            $this->item(
                'error_403_page',
                '403 – Zugriff verweigert',
                'Seitentyp 403 unter demselben Startpunkt',
                "SELECT error_page.id
                 FROM tl_page overview
                 INNER JOIN tl_page root ON root.id = overview.pid AND root.type = 'root'
                 INNER JOIN tl_page error_page ON error_page.pid = root.id AND error_page.type = 'error_403'
                 WHERE overview.type = 'regular' AND overview.alias = 'index'
                 ORDER BY error_page.id LIMIT 1"
            ),
            $this->item(
                'login_module',
                'Login-Modul',
                'Login-Formular auf der Login-Seite',
                "SELECT m.id
                 FROM tl_page p
                 INNER JOIN tl_article a ON a.pid = p.id
                 INNER JOIN tl_content c ON c.pid = a.id AND c.ptable = 'tl_article' AND c.type = 'module'
                 INNER JOIN tl_module m ON m.id = c.module AND m.type = 'login'
                 WHERE p.type = 'regular' AND p.alias = 'login'
                 ORDER BY c.id LIMIT 1"
            ),
            $this->item(
                'filter_element',
                'Inhaltselement Domainfilter',
                'Domainfilter auf der Übersichtsseite',
                "SELECT c.id FROM tl_content c INNER JOIN tl_article a ON a.id = c.pid AND c.ptable = 'tl_article' INNER JOIN tl_page p ON p.id = a.pid WHERE c.type = 'domain_manager_filter' AND p.alias = 'index' ORDER BY c.id LIMIT 1"
            ),
            $this->item(
                'overview_element',
                'Inhaltselement Domainübersicht',
                'Domainübersicht auf der Übersichtsseite',
                "SELECT c.id FROM tl_content c INNER JOIN tl_article a ON a.id = c.pid AND c.ptable = 'tl_article' INNER JOIN tl_page p ON p.id = a.pid WHERE c.type = 'domain_manager_overview' AND p.alias = 'index' ORDER BY c.id LIMIT 1"
            ),
        ];

        $found = count(array_filter(
            $items,
            static fn (array $item): bool => $item['found']
        ));
        $total = count($items);

        return [
            'complete' => $found === $total,
            'found' => $found,
            'total' => $total,
            'items' => $items,
        ];
    }

    /**
     * @param list<mixed> $parameters
     * @return array{key:string,label:string,expected:string,found:bool,id:int|null}
     */
    private function item(
        string $key,
        string $label,
        string $expected,
        string $sql,
        array $parameters = [],
    ): array {
        $id = $this->findId($sql, $parameters);

        return [
            'key' => $key,
            'label' => $label,
            'expected' => $expected,
            'found' => null !== $id,
            'id' => $id,
        ];
    }

    /** @param list<mixed> $parameters */
    private function findId(string $sql, array $parameters): ?int
    {
        try {
            $id = $this->connection->fetchOne($sql, $parameters);
        } catch (Throwable) {
            return null;
        }

        if (false === $id || null === $id || '' === (string) $id) {
            return null;
        }

        return (int) $id;
    }
}
