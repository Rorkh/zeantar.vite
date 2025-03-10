<?php

namespace Zeantar\Vite\Manifest;

use Zeantar\Vite\Exception\InvalidAssetFileException;
use Zeantar\Vite\Manifest\AssetFile;

class AssetFileFactory
{
    public function fromArray(array $asset): AssetFile
    {
        if (!isset($asset['file'])) {
            throw new InvalidAssetFileException('Invalid asset file: field "file" not found');
        }

        if (!isset($asset['src'])) {
            throw new InvalidAssetFileException('Invalid asset file: field "src" not found');
        }

        return new AssetFile(
            $asset['file'],
            $asset['name'],
            $asset['src'],
            $asset['isEntry'] ?? false,
            $asset['imports'] ?? [],
            $asset['dynamicImports'] ?? [],
            $asset['css'] ?? []
        );
    }
}