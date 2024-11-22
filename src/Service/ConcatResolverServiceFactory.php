<?php

namespace AssetManager\Service;

use AssetManager\Resolver\ConcatResolver;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Override;
use Psr\Container\ContainerInterface;

class ConcatResolverServiceFactory implements FactoryInterface
{
    /**
     * @inheritDoc
     */
    #[Override]
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null): ConcatResolver
    {
        $config = $container->get('config');
        $files  = [];

        if (isset($config['asset_manager']['resolver_configs']['concat'])) {
            $files = $config['asset_manager']['resolver_configs']['concat'];
        }

        return new ConcatResolver(concats: $files);
    }
}
