<?php

declare(strict_types=1);

namespace Lebensbaum\ContaoDomainManagerBundle\Event;

final class OverviewFrontendExtensionEvent
{
    /** @var list<array{name:string,label:string,url:string,capability:string,variant:string,confirm:string}> */
    private array $bulkActions = [];

    /** @param list<array<string, mixed>> $domains */
    public function __construct(public readonly array $domains)
    {
    }

    public function addBulkAction(
        string $name,
        string $label,
        string $url,
        string $capability,
        string $variant = 'default',
        string $confirm = '',
    ): void {
        $name = strtolower(trim($name));
        $label = trim($label);
        $url = trim($url);
        $capability = strtolower(trim($capability));
        $variant = strtolower(trim($variant));
        $confirm = trim($confirm);

        if (
            1 !== preg_match('/\A[a-z0-9_.-]+\z/', $name)
            || 1 !== preg_match('/\A[a-z0-9_.-]+\z/', $capability)
            || '' === $label
            || '' === $url
        ) {
            return;
        }

        if (!in_array($variant, ['default', 'primary', 'warning', 'danger'], true)) {
            $variant = 'default';
        }

        foreach ($this->bulkActions as $action) {
            if ($action['name'] === $name) {
                return;
            }
        }

        $this->bulkActions[] = [
            'name' => $name,
            'label' => $label,
            'url' => $url,
            'capability' => $capability,
            'variant' => $variant,
            'confirm' => $confirm,
        ];
    }

    /** @return list<array{name:string,label:string,url:string,capability:string,variant:string,confirm:string}> */
    public function getBulkActions(): array
    {
        return $this->bulkActions;
    }
}
