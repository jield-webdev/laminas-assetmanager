<?php

namespace AssetManager\Service;

use Assetic\Contracts\Asset\AssetInterface;
use AssetManager\Exception;
use AssetManager\Resolver\ResolverInterface;
use Laminas\Http\PhpEnvironment\Request;
use Laminas\Http\Response;
use Laminas\Stdlib\RequestInterface;
use Laminas\Uri\UriInterface;
use Override;

/**
 * @category    AssetManager
 * @package     AssetManager
 */
class AssetManager implements
    AssetFilterManagerAwareInterface,
    AssetCacheManagerAwareInterface
{
    /**
     * @var ResolverInterface
     */
    protected ResolverInterface $resolver;

    /**
     * @var AssetFilterManager The AssetFilterManager service.
     */
    protected AssetFilterManager $filterManager;

    /**
     * @var AssetCacheManager The AssetCacheManager service.
     */
    protected AssetCacheManager $cacheManager;

    /**
     * @var AssetInterface The asset
     */
    protected ?AssetInterface $asset = null;

    /**
     * @var string The requested path
     */
    protected string $path;

    /**
     * @var array The asset_manager configuration
     */
    protected array $config;

    /**
     * @var bool Whether this instance has at least one asset successfully set on response
     */
    protected bool $assetSetOnResponse = false;

    /**
     * Constructor
     *
     * @param ResolverInterface $resolver
     * @param array $config
     */
    public function __construct(ResolverInterface $resolver, array $config = [])
    {
        $this->setResolver(resolver: $resolver);
        $this->setConfig(config: $config);
    }

    /**
     * Set the config
     *
     * @param array $config
     */
    protected function setConfig(array $config): void
    {
        $this->config = $config;
    }

    /**
     * Check if the request resolves to an asset.
     */
    public function resolvesToAsset(RequestInterface $request): bool
    {
        if (null === $this->asset) {
            $this->asset = $this->resolve(request: $request);
        }

        return (bool)$this->asset;
    }

    /**
     * Returns true if this instance of asset manager has at least one asset successfully set on response
     *
     * @return bool
     */
    public function assetSetOnResponse(): bool
    {
        return $this->assetSetOnResponse;
    }

    /**
     * Set the resolver to use in the asset manager
     *
     * @param ResolverInterface $resolver
     */
    public function setResolver(ResolverInterface $resolver): void
    {
        $this->resolver = $resolver;
    }

    /**
     * Get the resolver used by the asset manager
     *
     * @return ResolverInterface
     */
    public function getResolver(): ResolverInterface
    {
        return $this->resolver;
    }

    /**
     * Set the asset on the response, including headers and content.
     *
     * @param Response $response
     * @return   Response
     * @throws   Exception\RuntimeException
     */
    public function setAssetOnResponse(Response $response): Response
    {
        if (!$this->asset instanceof \AssetManager\Asset\AssetInterface) {
            throw new Exception\RuntimeException(
                message: 'Unable to set asset on response. Request has not been resolved to an asset.'
            );
        }

        if (empty($this->asset->mimetype)) {
            throw new Exception\RuntimeException(message: 'Expected property "mimetype" on asset.');
        }

        $this->getAssetFilterManager()->setFilters(path: $this->path, asset: $this->asset);

        $this->asset   = $this->getAssetCacheManager()->setCache(path: $this->path, asset: $this->asset);
        $mimeType      = $this->asset->getMimetype();
        $assetContents = $this->asset->dump();
        // @codeCoverageIgnoreStart
        $contentLength = function_exists(function: 'mb_strlen') ? mb_strlen($assetContents, '8bit') : strlen(string: $assetContents);

        // @codeCoverageIgnoreEnd
        // Only clean the output buffer if it's turned on and something
        // has been buffered.
        if (!empty($this->config['clear_output_buffer']) && $this->config['clear_output_buffer'] && ob_get_length() > 0) {
            ob_clean();
        }

        $response->getHeaders()
            ->addHeaderLine('Content-Transfer-Encoding', 'binary')
            ->addHeaderLine('Content-Type', $mimeType)
            ->addHeaderLine('Content-Length', $contentLength);

        $response->setContent($assetContents);

        $this->assetSetOnResponse = true;

        return $response;
    }

    /**
     * Resolve the request to a file.
     *
     * @param RequestInterface $request
     *
     * @return mixed false when not found, AssetInterface when resolved.
     */
    protected function resolve(RequestInterface $request): mixed
    {
        if (!$request instanceof Request) {
            return false;
        }

        /* @var $request Request */
        /* @var $uri UriInterface */
        $uri        = $request->getUri();
        $fullPath   = $uri->getPath();
        $path       = substr(string: (string)$fullPath, offset: strlen(string: $request->getBasePath()) + 1);
        $this->path = $path;

        $asset = $this->getResolver()->resolve(fileName: $path);

        if (!$asset instanceof AssetInterface) {
            return false;
        }

        return $asset;
    }

    /**
     * Set the AssetFilterManager.
     *
     * @param AssetFilterManager $filterManager
     */
    #[Override]
    public function setAssetFilterManager(AssetFilterManager $filterManager): void
    {
        $this->filterManager = $filterManager;
    }

    /**
     * Get the AssetFilterManager
     *
     * @return AssetFilterManager
     */
    #[Override]
    public function getAssetFilterManager(): AssetFilterManager
    {
        return $this->filterManager;
    }

    /**
     * Set the AssetCacheManager.
     *
     * @param AssetCacheManager $cacheManager
     */
    #[Override]
    public function setAssetCacheManager(AssetCacheManager $cacheManager): void
    {
        $this->cacheManager = $cacheManager;
    }

    /**
     * Get the AssetCacheManager
     *
     * @return AssetCacheManager
     */
    #[Override]
    public function getAssetCacheManager(): AssetCacheManager
    {
        return $this->cacheManager;
    }
}
