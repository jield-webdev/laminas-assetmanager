<?php

namespace AssetManager\Service;

use Assetic\Cache\FilesystemCache;
use Assetic\Contracts\Cache\CacheInterface;
use AssetManager\Asset\AssetCache;
use AssetManager\Asset\AssetInterface;
use AssetManager\Cache\FilePathCache;
use Laminas\ServiceManager\ServiceLocatorInterface;
use Psr\Container\ContainerInterface;

/**
 * Asset Cache Manager.  Sets asset cache based on configuration.
 */
class AssetCacheManager
{
    /**
     * Construct the AssetCacheManager
     *
     * @param ServiceLocatorInterface $container
     * @param array $config
     */
    public function __construct(
        protected ContainerInterface $container,
        protected array              $config = []
    )
    {
    }

    /**
     * Set the cache (if any) on the asset, and return the new AssetCache.
     *
     * @param string $path Path to asset
     * @param AssetInterface $asset Assetic Asset Interface
     *
     * @return AssetInterface|AssetCache
     */
    public function setCache(string $path, AssetInterface $asset): AssetInterface|AssetCache
    {
        $provider = $this->getProvider(path: $path);

        if (!$provider instanceof CacheInterface) {
            return $asset;
        }

        $assetCache = new AssetCache(asset: $asset, cache: $provider);
        $assetCache->setMimetype($asset->getMimeType());

        return $assetCache;
    }

    /**
     * Get the cache provider.  First checks to see if the provider is callable,
     * then will attempt to get it from the service locator, finally will fallback
     * to a class mapper.
     */
    private function getProvider(string $path)
    {
        $cacheProvider = $this->getCacheProviderConfig(path: $path);

        if (!$cacheProvider) {
            return null;
        }

        if (is_string(value: $cacheProvider['cache']) &&
            $this->container->has($cacheProvider['cache'])
        ) {
            return $this->container->get($cacheProvider['cache']);
        }

        // Left here for BC.  Please consider defining a ZF2 service instead.
        if (is_callable(value: $cacheProvider['cache'])) {
            return call_user_func($cacheProvider['cache'], $path);
        }

        $dir   = '';
        $class = $cacheProvider['cache'];

        if (!empty($cacheProvider['options']['dir'])) {
            $dir = $cacheProvider['options']['dir'];
        }

        $class = $this->classMapper(class: $class);
        return new $class($dir, $path);
    }

    /**
     * Get the cache provider config.  Use default values if defined.
     *
     * @param $path
     *
     * @return null|array Cache config definition.  Returns null if not found in
     *                    config.
     */
    private function getCacheProviderConfig($path): ?array
    {
        $cacheProvider = null;

        if (!empty($this->config[$path]) && !empty($this->config[$path]['cache'])) {
            $cacheProvider = $this->config[$path];
        }

        if (!$cacheProvider
            && !empty($this->config['default'])
            && !empty($this->config['default']['cache'])
        ) {
            $cacheProvider = $this->config['default'];
        }

        return $cacheProvider;
    }

    /**
     * Class mapper to provide backwards compatibility
     *
     * @param string $class
     *
     * @return string
     */
    private function classMapper(string $class): string
    {
        $classToCheck = $class;
        $classToCheck .= (str_ends_with(haystack: $class, needle: 'Cache')) ? '' : 'Cache';

        return match ($classToCheck) {
            'FilesystemCache' => FilesystemCache::class,
            'FilePathCache'   => FilePathCache::class,
            default           => $class,
        };
    }
}
