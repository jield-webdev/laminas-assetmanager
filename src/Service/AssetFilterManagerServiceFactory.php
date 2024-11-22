<?php

namespace AssetManager\Service;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Override;
use Psr\Container\ContainerInterface;

class AssetFilterManagerServiceFactory implements FactoryInterface
{
    /**
     * @inheritDoc
     */
    #[Override]
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null): AssetFilterManager
    {
        $filters = [];
        $config  = $container->get('config');

        if (!empty($config['asset_manager']['filters'])) {
            $filters = $config['asset_manager']['filters'];
        }

        $assetFilterManager = new AssetFilterManager(config: $filters);

        $assetFilterManager->setContainer(container: $container);
        $assetFilterManager->setMimeResolver(mimeResolver: $container->get(MimeResolver::class));

        return $assetFilterManager;
    }
}
