<?php

namespace Zeantar\Vite\Manifest;

readonly final class AssetFile
{
    public function __construct(
        public string $filename,
        public string $name,
        public string $source,
        public bool $isEntry = false,
        public array $imports = [],
        public array $dynamicImports = [],
        public array $css = []
    ) {}
}