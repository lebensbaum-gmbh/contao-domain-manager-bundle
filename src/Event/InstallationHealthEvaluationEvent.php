<?php

declare(strict_types=1);

namespace Lebensbaum\ContaoDomainManagerBundle\Event;

final class InstallationHealthEvaluationEvent
{
    /** @var list<array{status:string,message:string}> */
    private array $issues = [];

    /** @var list<string> */
    private array $infoMessages = [];

    /** @param array<string, mixed> $installation */
    public function __construct(public readonly array $installation)
    {
    }

    public function addInfo(string $message): void
    {
        $message = trim($message);

        if ('' === $message || in_array($message, $this->infoMessages, true)) {
            return;
        }

        $this->infoMessages[] = $message;
    }

    public function addWarning(string $message): void
    {
        $this->addIssue('warning', $message);
    }

    public function addError(string $message): void
    {
        $this->addIssue('error', $message);
    }

    /** @return list<array{status:string,message:string}> */
    public function getIssues(): array
    {
        return $this->issues;
    }

    /** @return list<string> */
    public function getInfoMessages(): array
    {
        return $this->infoMessages;
    }

    private function addIssue(string $status, string $message): void
    {
        $message = trim($message);

        if ('' === $message) {
            return;
        }

        $this->issues[] = [
            'status' => $status,
            'message' => $message,
        ];
    }
}
