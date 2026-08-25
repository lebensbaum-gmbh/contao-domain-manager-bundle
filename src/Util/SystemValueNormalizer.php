<?php

declare(strict_types=1);

namespace Lebensbaum\ContaoDomainManagerBundle\Util;

use RuntimeException;

final class SystemValueNormalizer
{
    private function __construct()
    {
    }

    public static function webrootLabel(string $documentRoot): string
    {
        $normalized = str_replace('\\', '/', trim($documentRoot));
        $normalized = rtrim($normalized, '/');

        if ('' === $normalized) {
            return '';
        }

        return '/'.basename($normalized);
    }

    public static function contaoVersion(?string $version): string
    {
        $version = null !== $version ? trim($version) : '';

        if ('' === $version) {
            throw new RuntimeException('Die Contao-Version konnte nicht ermittelt werden.');
        }

        return preg_replace('/\Av(?=\d)/i', '', $version) ?? $version;
    }

    public static function phpVersion(string $version): string
    {
        $version = trim($version);

        if (1 !== preg_match('/\A(\d+)\.(\d+)/', $version, $matches)) {
            throw new RuntimeException(sprintf(
                'Die PHP-Version „%s“ konnte nicht verarbeitet werden.',
                $version
            ));
        }

        return $matches[1].'.'.$matches[2];
    }

    public static function phpVersionFull(string $version): string
    {
        $version = trim($version);

        if (1 !== preg_match('/\A(\d+)\.(\d+)(?:\.(\d+))?/', $version, $matches)) {
            throw new RuntimeException(sprintf(
                'Die PHP-Version „%s“ konnte nicht verarbeitet werden.',
                $version
            ));
        }

        $normalized = $matches[1].'.'.$matches[2];

        if (isset($matches[3]) && '' !== $matches[3]) {
            $normalized .= '.'.$matches[3];
        }

        return $normalized;
    }
}
