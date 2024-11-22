<?php

namespace AssetManager\Asset;

class AssetCollection extends \Assetic\Asset\AssetCollection implements AssetInterface
{
    public ?string $mimetype = null;

    public function getMimetype(): ?string
    {
        return $this->mimetype;
    }

    public function setMimetype(?string $mimetype = null): AssetCollection
    {
        $this->mimetype = $mimetype;
        return $this;
    }
}