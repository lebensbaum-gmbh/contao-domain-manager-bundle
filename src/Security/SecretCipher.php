<?php

declare(strict_types=1);

namespace Lebensbaum\ContaoDomainManagerBundle\Security;

final class SecretCipher
{
    private const PREFIX = 'v1:';
    private const IV_LENGTH = 12;
    private const TAG_LENGTH = 16;

    private readonly string $key;

    public function __construct(string $kernelSecret)
    {
        if ('' === trim($kernelSecret)) {
            throw new \InvalidArgumentException('Der Symfony-Kernel-Secret darf nicht leer sein.');
        }

        $this->key = hash_hkdf(
            'sha256',
            $kernelSecret,
            32,
            'contao-domain-manager-bundle'
        );
    }

    public function encrypt(string $plainText): string
    {
        if ('' === $plainText) {
            return '';
        }

        $iv = random_bytes(self::IV_LENGTH);
        $tag = '';
        $cipherText = openssl_encrypt(
            $plainText,
            'aes-256-gcm',
            $this->key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag,
            '',
            self::TAG_LENGTH
        );

        if (false === $cipherText || self::TAG_LENGTH !== strlen($tag)) {
            throw new \RuntimeException('Das Secret konnte nicht verschlüsselt werden.');
        }

        return self::PREFIX.base64_encode($iv.$tag.$cipherText);
    }

    public function decrypt(string $encryptedValue): string
    {
        if ('' === $encryptedValue) {
            return '';
        }

        if (!str_starts_with($encryptedValue, self::PREFIX)) {
            throw new \RuntimeException('Das gespeicherte Secret hat ein unbekanntes Format.');
        }

        $payload = base64_decode(substr($encryptedValue, strlen(self::PREFIX)), true);

        if (false === $payload || strlen($payload) <= self::IV_LENGTH + self::TAG_LENGTH) {
            throw new \RuntimeException('Das gespeicherte Secret ist beschädigt.');
        }

        $iv = substr($payload, 0, self::IV_LENGTH);
        $tag = substr($payload, self::IV_LENGTH, self::TAG_LENGTH);
        $cipherText = substr($payload, self::IV_LENGTH + self::TAG_LENGTH);

        $plainText = openssl_decrypt(
            $cipherText,
            'aes-256-gcm',
            $this->key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag
        );

        if (false === $plainText) {
            throw new \RuntimeException('Das gespeicherte Secret konnte nicht entschlüsselt werden.');
        }

        return $plainText;
    }
}
