<?php

declare(strict_types=1);

namespace AssetManager\Asset;
interface AssetInterface extends \Assetic\Contracts\Asset\AssetInterface
{
    public function setMimeType(?string $mimetype = null): self;

    public function getMimeType(): ?string;
}