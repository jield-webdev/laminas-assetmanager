<?php

use AssetManager\Resolver;
use AssetManager\Resolver\ResolverInterface;
use AssetManager\Service\MimeResolver;
use AssetManager\Service\AssetFilterManagerAwareInterface;

class InterfaceTestResolver implements
    Resolver\ResolverInterface,
    Resolver\AggregateResolverAwareInterface,
    Resolver\MimeResolverAwareInterface,
    AssetFilterManagerAwareInterface
{
    public $calledFilterManager;
    public $calledMime;
    public $calledAggregate;

    public function resolve(string $fileName)
    {
    }

    public function collect()
    {
    }

    public function getAggregateResolver()
    {

    }

    public function setAggregateResolver(ResolverInterface $resolver)
    {
        $this->calledAggregate = true;
    }

    public function setMimeResolver(MimeResolver $mimeResolver)
    {
        $this->calledMime = true;
    }

    public function getMimeResolver(): MimeResolver
    {
        return $this->calledMime;
    }

    public function getAssetFilterManager(): \AssetManager\Service\AssetFilterManager
    {

    }

    public function setAssetFilterManager(\AssetManager\Service\AssetFilterManager $filterManager)
    {
        $this->calledFilterManager = true;
    }
}
