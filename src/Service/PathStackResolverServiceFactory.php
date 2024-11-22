<?php

namespace AssetManager\Service;

use AssetManager\Resolver\PathStackResolver;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Override;
use Psr\Container\ContainerInterface;

class PathStackResolverServiceFactory implements FactoryInterface
{
    /**
     * @inheritDoc
     */
    #[Override]
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null): PathStackResolver
    {
        $config            = $container->get('config');
        $pathStackResolver = new PathStackResolver();
        $paths             = [];

        if (isset($config['asset_manager']['resolver_configs']['paths'])) {
            $paths = $config['asset_manager']['resolver_configs']['paths'];
        }

        $pathStackResolver->addPaths(paths: $paths);

        return $pathStackResolver;
    }
}
