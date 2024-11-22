<?php

namespace AssetManager\Asset;

class FileAsset extends \Assetic\Asset\FileAsset implements AssetInterface
{
    public ?string $mimetype = null;

    public function getMimetype(): ?string
    {
        return $this->mimetype;
    }

    public function setMimetype(?string $mimetype = null): FileAsset
    {
        $this->mimetype = $mimetype;
        return $this;
    }


}