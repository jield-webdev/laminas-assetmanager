<?php

namespace AssetManager\Resolver;

use AssetManager\Service\MimeResolver;

interface MimeResolverAwareInterface
{
    /**
     * Set the MimeResolver.
     */
    public function setMimeResolver(MimeResolver $mimeResolver);

    /**
     * Get the MimeResolver
     *
     * @return MimeResolver
     */
    public function getMimeResolver(): MimeResolver;
}
