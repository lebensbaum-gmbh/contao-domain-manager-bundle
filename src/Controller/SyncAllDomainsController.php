<?php

declare(strict_types=1);

namespace Lebensbaum\ContaoDomainManagerBundle\Controller;

use Contao\CoreBundle\Security\ContaoCorePermissions;
use Lebensbaum\ContaoDomainManagerBundle\Settings\DomainManagerSettings;
use Lebensbaum\ContaoDomainManagerBundle\Sync\AllDomainsSynchronizer;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Throwable;

#[AsController]
#[Route(
    path: '/domainverwaltung/synchronisieren',
    name: 'contao_domain_manager_sync_all',
    defaults: ['_scope' => 'frontend'],
    methods: ['POST'],
)]
final class SyncAllDomainsController
{
    public function __construct(
        private readonly AllDomainsSynchronizer $allDomainsSynchronizer,
        private readonly AuthorizationCheckerInterface $authorizationChecker,
        private readonly DomainManagerSettings $settings,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function __invoke(Request $request): Response
    {
        $memberGroupIds = $this->settings->getSyncMemberGroupIds();

        if (
            [] === $memberGroupIds
            || !$this->authorizationChecker->isGranted(
                ContaoCorePermissions::MEMBER_IN_GROUPS,
                $memberGroupIds
            )
        ) {
            throw new AccessDeniedHttpException(
                [] === $memberGroupIds
                    ? 'Die Frontend-Synchronisation ist noch keiner Mitgliedergruppe zugeordnet.'
                    : 'Keine Berechtigung für die Sammelsynchronisation.'
            );
        }

        try {
            $summary = $this->allDomainsSynchronizer->synchronize();

            $status = 0 === $summary['domains_failed']
                && 0 === $summary['domains_partial']
                ? 'success'
                : 'partial';

            $parameters = [
                'dm_sync_all' => $status,
                'dm_all_domains' => $summary['domains_total'],
                'dm_all_success' => $summary['domains_success'],
                'dm_all_partial' => $summary['domains_partial'],
                'dm_all_domain_failed' => $summary['domains_failed'],
                'dm_all_synced' => $summary['synchronized'],
                'dm_all_skipped' => $summary['skipped'],
                'dm_all_failed' => $summary['failed'],
                'dm_all_live_errors' => $summary['live_errors'],
            ];
        } catch (Throwable $exception) {
            $this->logger->error(
                'Die Sammelsynchronisation aller Hauptdomains ist fehlgeschlagen.',
                ['exception' => $exception]
            );

            $parameters = [
                'dm_sync_all' => 'error',
            ];
        }

        return new RedirectResponse(
            $this->createReturnUrl($request, $parameters),
            Response::HTTP_SEE_OTHER
        );
    }

    /**
     * @param array<string, int|string> $parameters
     */
    private function createReturnUrl(Request $request, array $parameters): string
    {
        $fallbackUrl = $request->getSchemeAndHttpHost().'/';
        $referer = $request->headers->get('referer');

        if (!is_string($referer) || '' === trim($referer)) {
            return $fallbackUrl;
        }

        $parts = parse_url($referer);

        if (
            false === $parts
            || !isset($parts['host'])
            || 0 !== strcasecmp($parts['host'], $request->getHost())
        ) {
            return $fallbackUrl;
        }

        $query = [];
        parse_str((string) ($parts['query'] ?? ''), $query);

        foreach ([
            'dm_sync_all',
            'dm_all_domains',
            'dm_all_success',
            'dm_all_partial',
            'dm_all_domain_failed',
            'dm_all_synced',
            'dm_all_skipped',
            'dm_all_failed',
            'dm_all_live_errors',
        ] as $key) {
            unset($query[$key]);
        }

        foreach ($parameters as $key => $value) {
            $query[$key] = (string) $value;
        }

        $scheme = $parts['scheme'] ?? $request->getScheme();
        $port = isset($parts['port']) ? ':'.$parts['port'] : '';
        $path = $parts['path'] ?? '/';
        $url = $scheme.'://'.$parts['host'].$port.$path;

        if ([] !== $query) {
            $url .= '?'.http_build_query($query, '', '&', PHP_QUERY_RFC3986);
        }

        if (isset($parts['fragment'])) {
            $url .= '#'.$parts['fragment'];
        }

        return $url;
    }
}
