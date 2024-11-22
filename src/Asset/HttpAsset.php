<?php

namespace AssetManager\Asset;

class HttpAsset extends \Assetic\Asset\HttpAsset implements AssetInterface
{
    public ?string $mimetype = null;

    public function getMimetype(): ?string
    {
        return $this->mimetype;
    }

    public function setMimetype(?string $mimetype = null): HttpAsset
    {
        $this->mimetype = $mimetype;
        return $this;
    }
}