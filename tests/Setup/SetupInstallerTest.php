<?php

declare(strict_types=1);

namespace Lebensbaum\ContaoDomainManagerBundle\Tests\Setup;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Schema\Schema;
use Lebensbaum\ContaoDomainManagerBundle\Setup\SetupInspector;
use Lebensbaum\ContaoDomainManagerBundle\Setup\SetupInstaller;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class SetupInstallerTest extends TestCase
{
    private ?Connection $connection = null;

    /** Minimal Contao tables used by the installer and inspector. */
    private const TABLES = [
        'tl_member_group' => ['tstamp', 'name'],
        'tl_theme' => ['tstamp', 'name', 'author'],
        'tl_layout' => ['pid', 'tstamp', 'name', 'type', 'rows', 'cols', 'modules', 'template'],
        'tl_page' => ['pid', 'sorting', 'tstamp', 'title', 'type', 'pageTitle', 'dns', 'language', 'fallback', 'includeLayout', 'layout', 'published', 'alias', 'protected', 'groups', 'cssClass', 'robots', 'autoforward', 'jumpTo'],
        'tl_module' => ['pid', 'tstamp', 'name', 'type', 'jumpTo', 'cssID'],
        'tl_article' => ['pid', 'sorting', 'tstamp', 'title', 'author', 'inColumn', 'published'],
        'tl_content' => ['pid', 'ptable', 'sorting', 'tstamp', 'type', 'text', 'headline', 'module'],
        'tl_domain_manager_settings' => ['tstamp', 'sync_member_groups', 'stale_sync_days'],
        'tl_member' => ['username', 'groups', 'login', 'disable', 'start', 'stop'],
    ];

    public static function databases(): iterable
    {
        yield 'SQLite' => ['sqlite'];
        yield 'MySQL 8' => ['mysql'];
    }

    #[DataProvider('databases')]
    public function testFreshSetupAndRepairPreservePageProtectionAndRecognizeMembers(string $database): void
    {
        if ('mysql' === $database) {
            if (!getenv('MAINTEND_TEST_MYSQL_HOST')) {
                self::markTestSkipped('Set MAINTEND_TEST_MYSQL_* to run the MySQL integration test.');
            }

            $parameters = [
                'driver' => 'pdo_mysql',
                'host' => getenv('MAINTEND_TEST_MYSQL_HOST'),
                'port' => (int) (getenv('MAINTEND_TEST_MYSQL_PORT') ?: 3306),
                'dbname' => getenv('MAINTEND_TEST_MYSQL_DATABASE'),
                'user' => getenv('MAINTEND_TEST_MYSQL_USER'),
                'password' => getenv('MAINTEND_TEST_MYSQL_PASSWORD'),
                'charset' => 'utf8mb4',
            ];
        } else {
            $parameters = ['driver' => 'pdo_sqlite', 'memory' => true];
        }

        $this->connection = DriverManager::getConnection($parameters);
        $this->createSchema();
        $inspector = new SetupInspector($this->connection);
        $installer = new SetupInstaller($this->connection, $inspector);

        $result = $installer->completeSetup('setup.example.test');
        self::assertTrue($result['complete']);
        self::assertNotEmpty($result['created']);
        self::assertSame(6, (int) $this->connection->fetchOne('SELECT COUNT(*) FROM tl_page'));

        $groupId = (int) $this->connection->fetchOne('SELECT id FROM tl_member_group');
        $pages = $this->connection->fetchAllAssociative("SELECT * FROM tl_page WHERE alias IN ('index', 'updates') ORDER BY id");
        self::assertCount(2, $pages);
        foreach ($pages as $page) {
            self::assertSame(1, (int) $page['protected']);
            self::assertSame([$groupId], unserialize($page['groups']));
        }

        self::assertSame(['created' => [], 'complete' => true], $installer->completeSetup('setup.example.test'));

        // Force a partial repair and retain an existing group and CSS class.
        $pageId = (int) $pages[0]['id'];
        $this->connection->update('tl_page', [
            '`groups`' => serialize([99]),
            'cssClass' => 'custom-class',
            'protected' => 0,
        ], ['id' => $pageId]);
        $this->connection->delete('tl_content', ['type' => 'domain_manager_updates']);
        self::assertTrue($installer->completeSetup('setup.example.test')['complete']);
        self::assertSame(6, (int) $this->connection->fetchOne('SELECT COUNT(*) FROM tl_page'));
        $page = $this->connection->fetchAssociative('SELECT * FROM tl_page WHERE id = ?', [$pageId]);
        self::assertSame(1, (int) $page['protected']);
        self::assertSame([99, $groupId], unserialize($page['groups']));
        self::assertSame('custom-class domainverwaltung-page', $page['cssClass']);

        self::assertFalse($inspector->inspect()['frontend_access']['ready']);
        $this->connection->insert('tl_member', [
            'username' => 'setup-test',
            '`groups`' => serialize([$groupId]),
            'login' => 1,
            'disable' => 0,
            'start' => '',
            'stop' => '',
        ]);
        $access = $inspector->inspect()['frontend_access'];
        self::assertTrue($access['ready']);
        self::assertSame('setup-test', $access['username']);
    }

    protected function tearDown(): void
    {
        if (null !== $this->connection) {
            foreach (array_reverse(array_keys(self::TABLES)) as $table) {
                $this->connection->executeStatement('DROP TABLE IF EXISTS '.$this->connection->quoteIdentifier($table));
            }
            $this->connection->close();
        }
    }

    private function createSchema(): void
    {
        $schema = new Schema();
        foreach (self::TABLES as $name => $columns) {
            $table = $schema->createTable($name);
            $table->addColumn('id', 'integer', ['autoincrement' => true]);
            $table->setPrimaryKey(['id']);
            foreach ($columns as $column) {
                $type = in_array($column, ['pid', 'sorting', 'tstamp', 'layout', 'includeLayout', 'module', 'jumpTo'], true) ? 'integer' : 'text';
                $table->addColumn('`'.$column.'`', $type, ['notnull' => false]);
            }
        }
        foreach ($schema->toSql($this->connection->getDatabasePlatform()) as $sql) {
            $this->connection->executeStatement($sql);
        }
    }
}
