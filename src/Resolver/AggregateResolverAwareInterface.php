<?php

namespace AssetManager\Resolver;

interface AggregateResolverAwareInterface
{
    /**
     * Set the aggregate resolver.
     */
    public function setAggregateResolver(ResolverInterface $aggregateResolver);

    /**
     * Get the aggregate resolver.
     */
    public function getAggregateResolver(): ResolverInterface;
}
