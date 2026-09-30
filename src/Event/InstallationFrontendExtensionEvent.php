<?php

declare(strict_types=1);

namespace Lebensbaum\ContaoDomainManagerBundle\Event;

final class InstallationFrontendExtensionEvent
{
    /** @var list<array{name:string,label:string,url:string,method:string,variant:string,confirm:string}> */
    private array $actions = [];

    /** @var list<string> */
    private array $bulkCapabilities = [];

    /** @var list<array{template:string,context:array<string,mixed>}> */
    private array $sections = [];

    /** @var array<string, mixed> */
    private array $metadata = [];

    /** @param array<string, mixed> $installation */
    public function __construct(public readonly array $installation)
    {
    }

    public function addAction(
        string $name,
        string $label,
        string $url,
        string $method = 'get',
        string $variant = 'default',
        string $confirm = '',
    ): void {
        $name = strtolower(trim($name));
        $label = trim($label);
        $url = trim($url);
        $method = strtolower(trim($method));
        $variant = strtolower(trim($variant));
        $confirm = trim($confirm);

        if (1 !== preg_match('/\A[a-z0-9_.-]+\z/', $name) || '' === $label || '' === $url) {
            return;
        }

        if (!in_array($method, ['get', 'post'], true)) {
            $method = 'get';
        }

        if (!in_array($variant, ['default', 'primary', 'warning', 'danger'], true)) {
            $variant = 'default';
        }

        foreach ($this->actions as $action) {
            if ($action['name'] === $name) {
                return;
            }
        }

        $this->actions[] = [
            'name' => $name,
            'label' => $label,
            'url' => $url,
            'method' => $method,
            'variant' => $variant,
            'confirm' => $confirm,
        ];
    }

    public function addBulkCapability(string $capability): void
    {
        $capability = strtolower(trim($capability));

        if (1 !== preg_match('/\A[a-z0-9_.-]+\z/', $capability)) {
            return;
        }

        if (!in_array($capability, $this->bulkCapabilities, true)) {
            $this->bulkCapabilities[] = $capability;
        }
    }

    /** @param array<string, mixed> $context */
    public function addSection(string $template, array $context = []): void
    {
        $template = trim($template);

        if ('' === $template) {
            return;
        }

        $this->sections[] = [
            'template' => $template,
            'context' => $context,
        ];
    }

    /** @return list<array{name:string,label:string,url:string,method:string,variant:string,confirm:string}> */
    public function getActions(): array
    {
        return $this->actions;
    }

    /** @return list<string> */
    public function getBulkCapabilities(): array
    {
        return $this->bulkCapabilities;
    }

    public function setMetadata(string $name, mixed $value): void
    {
        $name = strtolower(trim($name));

        if (1 !== preg_match('/\A[a-z0-9_.-]+\z/', $name)) {
            return;
        }

        $this->metadata[$name] = $value;
    }

    /** @return array<string, mixed> */
    public function getMetadata(): array
    {
        return $this->metadata;
    }

    /** @return list<array{template:string,context:array<string,mixed>}> */
    public function getSections(): array
    {
        return $this->sections;
    }
}
