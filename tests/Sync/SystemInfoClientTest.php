<?php

declare(strict_types=1);

namespace Lebensbaum\ContaoDomainManagerBundle\Tests\Sync;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Lebensbaum\ContaoDomainManagerBundle\Connection\SystemInfoConnectionException;
use Lebensbaum\ContaoDomainManagerBundle\Security\SecretCipher;
use Lebensbaum\ContaoDomainManagerBundle\Security\SecretStore;
use Lebensbaum\ContaoDomainManagerBundle\Sync\SystemInfoApiClient;
use Lebensbaum\ContaoDomainManagerBundle\Sync\SystemInfoClient;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class SystemInfoClientTest extends TestCase
{
    private const SYSTEM_ID = '0123456789abcdef0123456789abcdef';
    private const SECRET = '0123456789abcdef0123456789abcdef0123456789abcdef0123456789abcdef';

    public function testClassifiesHttp500WithoutJsonAsRemoteServerError(): void
    {
        $client = $this->createClient(new MockResponse('<html>Internal Server Error</html>', [
            'http_code' => 500,
        ]));

        try {
            $client->fetch('example.test', self::SYSTEM_ID);
            self::fail('Expected SystemInfoConnectionException.');
        } catch (SystemInfoConnectionException $exception) {
            self::assertSame('endpoint', $exception->getStage());
            self::assertSame('remote_server_error', $exception->getErrorCode());
            self::assertSame(500, $exception->getHttpStatus());
            self::assertStringContainsString('PHP-Version', $exception->getMessage());
        }
    }

    public function testClassifiesMissingEndpoint(): void
    {
        $client = $this->createClient(new MockResponse('Not Found', [
            'http_code' => 404,
        ]));

        try {
            $client->fetch('example.test', self::SYSTEM_ID);
            self::fail('Expected SystemInfoConnectionException.');
        } catch (SystemInfoConnectionException $exception) {
            self::assertSame('endpoint', $exception->getStage());
            self::assertSame('endpoint_not_found', $exception->getErrorCode());
            self::assertSame(404, $exception->getHttpStatus());
        }
    }

    public function testDistinguishesClockSkewFromInvalidCredentials(): void
    {
        $client = $this->createClient(new MockResponse(json_encode([
            'error' => 'unauthorized',
            'server_time' => time() - 900,
        ], JSON_THROW_ON_ERROR), [
            'http_code' => 401,
            'response_headers' => ['content-type: application/json'],
        ]));

        try {
            $client->fetch('example.test', self::SYSTEM_ID);
            self::fail('Expected SystemInfoConnectionException.');
        } catch (SystemInfoConnectionException $exception) {
            self::assertSame('authentication', $exception->getStage());
            self::assertSame('clock_skew', $exception->getErrorCode());
            self::assertSame(401, $exception->getHttpStatus());
            self::assertStringContainsString('Zeitabweichung', $exception->getMessage());
        }
    }

    public function testAcceptsVersionedSuccessfulResponse(): void
    {
        $client = $this->createClient(new MockResponse(json_encode([
            'api_version' => 1,
            'system_info_version' => '1.3.0',
            'system_id' => self::SYSTEM_ID,
            'contao_version' => '5.7.13',
            'php_version' => '8.5.3-nmm1',
            'database_name' => 'contao',
            'document_root' => '/var/www/public',
            'app_environment' => 'prod',
            'generated_at' => '2026-09-01T12:00:00+00:00',
        ], JSON_THROW_ON_ERROR), [
            'http_code' => 200,
            'response_headers' => ['content-type: application/json'],
        ]));

        $data = $client->fetch('example.test', self::SYSTEM_ID);

        self::assertSame(1, $data['api_version']);
        self::assertSame('1.3.0', $data['system_info_version']);
        self::assertSame('5.7.13', $data['contao_version']);
        self::assertSame('8.5.3-nmm1', $data['php_version']);
    }

    private function createClient(MockResponse $response): SystemInfoClient
    {
        $cipher = new SecretCipher('test-kernel-secret');
        $encryptedSecret = $cipher->encrypt(self::SECRET);
        $schemaManager = $this->createMock(AbstractSchemaManager::class);
        $schemaManager
            ->method('tablesExist')
            ->with(['tl_domain_manager_installation'])
            ->willReturn(true);
        $schemaManager
            ->method('listTableColumns')
            ->with('tl_domain_manager_installation')
            ->willReturn([
                'dm_encrypted_secret' => new \stdClass(),
                'dm_secret_changed_at' => new \stdClass(),
            ]);

        $connection = $this->createMock(Connection::class);
        $connection
            ->method('createSchemaManager')
            ->willReturn($schemaManager);
        $connection
            ->method('fetchAllAssociative')
            ->willReturn([
                [
                    'id' => 1,
                    'dm_encrypted_secret' => $encryptedSecret,
                ],
            ]);

        $secretStore = new SecretStore($connection, $cipher);
        $httpClient = new MockHttpClient($response, 'https://example.test');
        $apiClient = new SystemInfoApiClient($httpClient, $secretStore);

        return new SystemInfoClient($apiClient);
    }
}
