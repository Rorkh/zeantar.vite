<?php

namespace Zeantar\Vite\Manifest;

use Zeantar\Vite\Exception\InvalidManifestException;
use Zeantar\Vite\Manifest\AssetFileFactory;

final class Manifest
{
    public array $files = [];

    public function __construct(string $path)
    {
        $rawManifest = file_get_contents($path);
        $manifest = json_decode($rawManifest, true);

        if (!json_validate($rawManifest)) {
            throw new InvalidManifestException();
        }

        $factory = new AssetFileFactory;

        foreach ($manifest as $asset) {
            $this->files[] = $factory->fromArray($asset);
        }
    }
}