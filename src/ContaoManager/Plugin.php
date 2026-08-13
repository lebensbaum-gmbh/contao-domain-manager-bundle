<?php

declare(strict_types=1);

namespace Lebensbaum\ContaoDomainManagerBundle\ContaoManager;

use Contao\CoreBundle\ContaoCoreBundle;
use Contao\ManagerPlugin\Bundle\BundlePluginInterface;
use Contao\ManagerPlugin\Bundle\Config\BundleConfig;
use Contao\ManagerPlugin\Bundle\Parser\ParserInterface;
use Contao\ManagerPlugin\Routing\RoutingPluginInterface;
use Lebensbaum\ContaoDomainManagerBundle\ContaoDomainManagerBundle;
use Symfony\Component\Config\Loader\LoaderResolverInterface;
use Symfony\Component\HttpKernel\KernelInterface;

final class Plugin implements BundlePluginInterface, RoutingPluginInterface
{
    public function getBundles(ParserInterface $parser): array
    {
        return [
            BundleConfig::create(ContaoDomainManagerBundle::class)
                ->setLoadAfter([
                    ContaoCoreBundle::class,
                ]),
        ];
    }

    public function getRouteCollection(
        LoaderResolverInterface $resolver,
        KernelInterface $kernel,
    ) {
        $file = __DIR__.'/../../config/routes.yaml';

        return $resolver
            ->resolve($file)
            ->load($file)
        ;
    }
}