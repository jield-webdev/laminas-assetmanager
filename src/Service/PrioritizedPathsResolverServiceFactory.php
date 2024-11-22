<?php

namespace AssetManager\Service;

use AssetManager\Resolver\PrioritizedPathsResolver;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Override;
use Psr\Container\ContainerInterface;

class PrioritizedPathsResolverServiceFactory implements FactoryInterface
{
    /**
     * @inheritDoc
     */
    #[Override]
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null): PrioritizedPathsResolver
    {
        $config                   = $container->get('config');
        $prioritizedPathsResolver = new PrioritizedPathsResolver();
        $paths                    = $config['asset_manager']['resolver_configs']['prioritized_paths'] ?? [];
        $prioritizedPathsResolver->addPaths(paths: $paths);

        return $prioritizedPathsResolver;
    }
}
