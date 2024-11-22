<?php

namespace AssetManager\Resolver;

use Laminas\Stdlib\PriorityQueue;
use Override;

/**
 * The aggregate resolver consists out of a multitude of
 * resolvers defined by the ResolverInterface.
 */
class AggregateResolver implements ResolverInterface
{
    /**
     * @var PriorityQueue|ResolverInterface[]
     */
    protected array|PriorityQueue $queue;

    /**
     * Constructor
     *
     * Instantiate the internal priority queue
     */
    public function __construct()
    {
        $this->queue = new PriorityQueue();
    }

    /**
     * Attach a resolver
     *
     * @param ResolverInterface $resolver
     * @param int $priority
     * @return self
     */
    public function attach(ResolverInterface $resolver, int $priority = 1): AggregateResolver
    {
        $this->queue->insert(data: $resolver, priority: $priority);

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    #[Override]
    public function resolve(string $fileName): ?\Assetic\Contracts\Asset\AssetInterface
    {
        foreach ($this->queue as $resolver) {
            $resource = $resolver->resolve(fileName: $fileName);
            if (null !== $resource) {
                return $resource;
            }
        }

        return null;
    }

    public function collect(): array
    {
        $collection = [];

        foreach ($this->queue as $resolver) {
            if (!method_exists(object_or_class: $resolver, method: 'collect')) {
                continue;
            }

            $collection = array_merge($resolver->collect(), $collection);
        }

        return array_unique(array: $collection);
    }
}
