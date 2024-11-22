<?php

namespace AssetManager\Service;

use AssetManager\Resolver\MapResolver;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Override;
use Psr\Container\ContainerInterface;

class MapResolverServiceFactory implements FactoryInterface
{
    /**
     * @inheritDoc
     */
    #[Override]
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null): MapResolver
    {
        $config = $container->get('config');
        $map    = [];

        if (isset($config['asset_manager']['resolver_configs']['map'])) {
            $map = $config['asset_manager']['resolver_configs']['map'];
        }

        return new MapResolver(map: $map);
    }
}
