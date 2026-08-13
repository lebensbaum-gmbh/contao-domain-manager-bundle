<?php

declare(strict_types=1);

namespace Lebensbaum\ContaoDomainManagerBundle\Controller;

use Contao\CoreBundle\Security\ContaoCorePermissions;
use Lebensbaum\ContaoDomainManagerBundle\Settings\DomainManagerSettings;
use Lebensbaum\ContaoDomainManagerBundle\Sync\DomainSynchronizer;
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
    path: '/domainverwaltung/synchronisieren/{domainId}',
    name: 'contao_domain_manager_sync_domain',
    requirements: ['domainId' => '\\d+'],
    defaults: ['_scope' => 'frontend'],
    methods: ['POST'],
)]
final class SyncDomainController
{
    public function __construct(
        private readonly DomainSynchronizer $domainSynchronizer,
        private readonly AuthorizationCheckerInterface $authorizationChecker,
        private readonly DomainManagerSettings $settings,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function __invoke(int $domainId, Request $request): Response
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
                    : 'Keine Berechtigung für die Domain-Synchronisation.'
            );
        }

        try {
            $result = $this->domainSynchronizer->synchronize($domainId);

            $liveResult = $result['live'];
            $liveError = $result['live_error'];

            $parameters = [
                'dm_sync' => ([] === $result['failed'] && null === $liveError) ? 'success' : 'partial',
                'dm_domain' => $domainId,
                'dm_synced' => count($result['synchronized']),
                'dm_skipped' => count($result['skipped']),
                'dm_failed' => count($result['failed']),
            ];

            if (is_array($liveResult)) {
                $parameters['dm_live'] = $liveResult['changed'] ? 'changed' : 'unchanged';
                $parameters['dm_live_domain'] = $liveResult['installation_domain'];
            } elseif (null !== $liveError) {
                $parameters['dm_live'] = 'error';
            }
        } catch (Throwable $exception) {
            $this->logger->error(
                'Die Synchronisation einer Hauptdomain ist fehlgeschlagen.',
                [
                    'domain_id' => $domainId,
                    'exception' => $exception,
                ]
            );

            $parameters = [
                'dm_sync' => 'error',
                'dm_domain' => $domainId,
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

        unset(
            $query['dm_sync'],
            $query['dm_domain'],
            $query['dm_synced'],
            $query['dm_skipped'],
            $query['dm_failed'],
            $query['dm_live'],
            $query['dm_live_domain']
        );

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
