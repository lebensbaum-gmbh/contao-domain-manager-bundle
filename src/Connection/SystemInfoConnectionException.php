<?php

declare(strict_types=1);

namespace Lebensbaum\ContaoDomainManagerBundle\Connection;

use RuntimeException;
use Throwable;

final class SystemInfoConnectionException extends RuntimeException
{
    public function __construct(
        private readonly string $stage,
        private readonly string $errorCode,
        string $message,
        private readonly ?int $httpStatus = null,
        private readonly ?string $technicalDetails = null,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }

    public function getStage(): string
    {
        return $this->stage;
    }

    public function getErrorCode(): string
    {
        return $this->errorCode;
    }

    public function getHttpStatus(): ?int
    {
        return $this->httpStatus;
    }

    public function getTechnicalDetails(): ?string
    {
        return $this->technicalDetails;
    }
}
