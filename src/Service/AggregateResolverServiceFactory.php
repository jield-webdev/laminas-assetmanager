<?php

namespace AssetManager\Service;

use AssetManager\Exception;
use AssetManager\Resolver\AggregateResolver;
use AssetManager\Resolver\AggregateResolverAwareInterface;
use AssetManager\Resolver\MimeResolverAwareInterface;
use AssetManager\Resolver\ResolverInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Override;
use Psr\Container\ContainerInterface;

/**
 * Factory class for AssetManagerService
 *
 * @category   AssetManager
 * @package    AssetManager
 */
class AggregateResolverServiceFactory implements FactoryInterface
{
    /**
     * @inheritDoc
     */
    #[Override]
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null): AggregateResolver
    {
        $config = $container->get('config');
        $config = $config['asset_manager'] ?? [];

        $resolver = new AggregateResolver();

        if (empty($config['resolvers'])) {
            return $resolver;
        }

        foreach ($config['resolvers'] as $resolverService => $priority) {

            $resolverService = $container->get($resolverService);

            if (!$resolverService instanceof ResolverInterface) {
                throw new Exception\RuntimeException(
                    message: 'Service does not implement the required interface ResolverInterface.'
                );
            }

            if ($resolverService instanceof AggregateResolverAwareInterface) {
                $resolverService->setAggregateResolver($resolver);
            }

            if ($resolverService instanceof MimeResolverAwareInterface) {
                $resolverService->setMimeResolver($container->get(MimeResolver::class));
            }

            if ($resolverService instanceof AssetFilterManagerAwareInterface) {
                $resolverService->setAssetFilterManager(
                    filterManager: $container->get(AssetFilterManager::class)
                );
            }

            $resolver->attach(resolver: $resolverService, priority: $priority);
        }

        return $resolver;
    }
}
