<?php

declare(strict_types=1);

namespace Lebensbaum\ContaoDomainManagerBundle\Tests\Health;

use Lebensbaum\ContaoDomainManagerBundle\Health\InstallationHealthEvaluator;
use PHPUnit\Framework\TestCase;

final class InstallationHealthEvaluatorTest extends TestCase
{
    private const NOW = 1_800_000_000;
    private const SYSTEM_ID = '0123456789abcdef0123456789abcdef';

    public function testHealthyInstallationIsOk(): void
    {
        $health = $this->evaluator()->evaluate([
            'sync_status' => 'success',
            'connection_status' => 'success',
            'system_id' => self::SYSTEM_ID,
            'last_sync' => self::NOW - 86400,
            'document_root' => '/public',
        ], 30, self::NOW);

        self::assertSame(InstallationHealthEvaluator::STATUS_OK, $health['status']);
        self::assertSame('OK', $health['label']);
        self::assertSame([], $health['messages']);
        self::assertSame(0, $health['issue_count']);
    }

    public function testWebDirectoryCreatesWarning(): void
    {
        $health = $this->evaluator()->evaluate([
            'sync_status' => 'success',
            'connection_status' => 'success',
            'system_id' => self::SYSTEM_ID,
            'last_sync' => self::NOW,
            'document_root' => '/web',
        ], 30, self::NOW);

        self::assertSame(InstallationHealthEvaluator::STATUS_WARNING, $health['status']);
        self::assertContains('Der Webroot verwendet noch das veraltete Verzeichnis /web.', $health['messages']);
    }

    public function testStaleSynchronizationCreatesWarning(): void
    {
        $health = $this->evaluator()->evaluate([
            'sync_status' => 'success',
            'connection_status' => 'success',
            'system_id' => self::SYSTEM_ID,
            'last_sync' => self::NOW - (31 * 86400),
            'document_root' => '/public',
        ], 30, self::NOW);

        self::assertSame(InstallationHealthEvaluator::STATUS_WARNING, $health['status']);
        self::assertContains('Die letzte erfolgreiche Synchronisation ist älter als 30 Tage.', $health['messages']);
    }

    public function testMissingSystemIdAndSynchronizationCreateWarnings(): void
    {
        $health = $this->evaluator()->evaluate([
            'sync_status' => '',
            'connection_status' => 'not_configured',
            'system_id' => '',
            'last_sync' => 0,
            'document_root' => '',
        ], 30, self::NOW);

        self::assertSame(InstallationHealthEvaluator::STATUS_WARNING, $health['status']);
        self::assertGreaterThanOrEqual(3, $health['issue_count']);
    }

    public function testSynchronizationErrorWinsOverWarnings(): void
    {
        $health = $this->evaluator()->evaluate([
            'sync_status' => 'error',
            'connection_status' => 'error',
            'system_id' => '',
            'last_sync' => 0,
            'document_root' => '/web',
        ], 30, self::NOW);

        self::assertSame(InstallationHealthEvaluator::STATUS_ERROR, $health['status']);
        self::assertSame('Fehler', $health['label']);
    }

    public function testDomainSummaryUsesWorstInstallationState(): void
    {
        $health = $this->evaluator()->summarize([
            ['status' => InstallationHealthEvaluator::STATUS_OK, 'issue_count' => 0],
            ['status' => InstallationHealthEvaluator::STATUS_WARNING, 'issue_count' => 2],
            ['status' => InstallationHealthEvaluator::STATUS_ERROR, 'issue_count' => 1],
        ], true);

        self::assertSame(InstallationHealthEvaluator::STATUS_ERROR, $health['status']);
        self::assertSame(3, $health['issue_count']);
    }

    public function testMissingCurrentTargetCreatesDomainWarning(): void
    {
        $health = $this->evaluator()->summarize([
            ['status' => InstallationHealthEvaluator::STATUS_OK, 'issue_count' => 0],
        ], false);

        self::assertSame(InstallationHealthEvaluator::STATUS_WARNING, $health['status']);
        self::assertSame(1, $health['issue_count']);
        self::assertContains('Für diese Hauptdomain ist kein aktuelles Ziel festgelegt.', $health['messages']);
    }

    private function evaluator(): InstallationHealthEvaluator
    {
        return new InstallationHealthEvaluator();
    }
}
