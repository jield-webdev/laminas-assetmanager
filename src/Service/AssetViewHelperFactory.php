<?php

namespace AssetManager\Service;

use AssetManager\Exception\InvalidArgumentException;
use AssetManager\Resolver\ResolverInterface;
use AssetManager\View\Helper\Asset;
use Laminas\Cache\Storage\Adapter\AbstractAdapter as AbstractCacheAdapter;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Laminas\ServiceManager\ServiceLocatorInterface;
use Override;
use Psr\Container\ContainerInterface;

class AssetViewHelperFactory implements FactoryInterface
{
    /**
     * @inheritDoc
     */
    #[Override]
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null): Asset
    {
        $config = $container->get('config')['asset_manager'];

        /** @var ResolverInterface $assetManagerResolver */
        $assetManagerResolver = $container->get(AssetManager::class)->getResolver();

        /** @var AbstractCacheAdapter|null $cache */
        $cache = $this->loadCache(container: $container, config: $config);

        return new Asset(assetManagerResolver: $assetManagerResolver, cache: $cache, config: $config);
    }

    /**
     * @param ServiceLocatorInterface $container
     * @param array $config
     *
     * @return null
     */
    private function loadCache(ContainerInterface $container, array $config): null
    {
        // check if the cache is configured
        if (!isset($config['view_helper']['cache'])) {
            return null;
        }

        // get the cache, if it's a string, search it among services
        $cache = $config['view_helper']['cache'];
        if (is_string(value: $cache)) {
            $cache = $container->get($cache);
        }

        // exception in case cache is not an Adapter that extend the AbstractAdapter of Laminas\Cache\Storage
        if ($cache !== null && !($cache instanceof AbstractCacheAdapter)) {
            throw new InvalidArgumentException(
                message: 'Invalid cache provided, you must pass a Cache Adapter that extend 
                Laminas\Cache\Storage\Adapter\AbstractAdapter'
            );
        }

        return $cache;
    }
}
