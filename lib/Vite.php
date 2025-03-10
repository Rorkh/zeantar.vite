<?php

namespace Zeantar\Vite;

use Bitrix\Main\Config\Option;
use Zeantar\Vite\Dynamic\Application;
use Zeantar\Vite\Dynamic\VueApplication;
use Zeantar\Vite\Exception\InvalidManifestException;
use Zeantar\Vite\Exception\ManifestLocationException;
use Zeantar\Vite\Exception\ViteNotFoundException;
use Zeantar\Vite\Manifest\{Manifest, AssetFile};
use Zeantar\Vite\Dynamic\ApplicationMount;

final class Vite
{
    /**
     * Default Vite location
     */
    public const DEFAULT_VITE_FOLDER = '/local/vite/';

    /**
     * Singleton instance
     *
     * @var Vite|null
     */
    private static ?Vite $instance = null;

    /**
     * Actual Vite location (full path)
     * 
     * Either default location or location determined in constructor.
     *
     * @var string
     */
    private string $viteFolder;

    /**
     * Is Vite assets injected in a current hit
     *
     * @var boolean
     */
    protected bool $injected = false;
    
    /**
     * @param string|null $viteFolder Full path to Vite folder
     */
    public function __construct(?string $viteFolder = null)
    {
        if (is_null($viteFolder)) {
            $viteFolder = $_SERVER['DOCUMENT_ROOT'] . self::DEFAULT_VITE_FOLDER;
        }

        if (!file_exists($viteFolder)) {
            throw new ViteNotFoundException(
                "Vite not found at: \"$viteFolder\""
            );
        }

        $this->viteFolder = $viteFolder;
    }

    /**
     * Get Vite instance
     * 
     * @return Vite
     */
    public static function getInstance(): Vite
    {
        if (is_null(self::$instance)) {
            self::$instance = new self;
        }

        return self::$instance;
    }

    /**
     * Is Vite enabled
     * 
     * @return boolean
     */
    public function isEnabled(): bool
    {
        return Option::get('zeantar.vite', 'VITE_ENABLED') == 'Y' ??
            constant('VITE_ENABLED') == true;
    }

    /**
     * Is Vite debug mode enabled
     * 
     * @return boolean
     */
    public function isDebugEnabled(): bool
    {
        return Option::get('zeantar.vite', 'VITE_DEBUG') == 'Y' ??
            constant('VITE_DEBUG') == true;
    }

    /**
     * Is autoload enabled
     * 
     * When enabled, Vite asset files will be automatically injected into page content.
     * 
     * @return boolean
     */
    public function isAutoloadEnabled(): bool
    {
        return Option::get('zeantar.vite', 'VITE_AUTO_LOAD') == 'Y';
    }

    /**
     * Is dynamic features enabled
     * 
     * @return boolean
     */
    public function isDynamicEnabled(): bool
    {
        return Option::get('zeantar.vite', 'VITE_DYNAMIC') == 'Y';
    }

    /**
     * Is Vite assets was injected into current page
     *
     * @return boolean
     */
    public function isInjected(): bool
    {
        return $this->injected;
    }

    /**
     * @param boolean $injected Is Vite assets injected
     * @return void
     */
    public function setInjected(bool $injected): void
    {
        $this->injected = $injected;
    }

    private function locateManifest(?string $manifestFile = null): ?string
    {
        if (is_null($manifestFile)) {
            $manifestFile = $_SERVER['DOCUMENT_ROOT'] . self::DEFAULT_VITE_FOLDER
                . 'dist/.vite/manifest.json';
        }

        if (file_exists($manifestFile)) {
            return $manifestFile;
        }

        return null;
    }

    private function validateManifest(?string $manifestFile = null): bool
    {
        return json_validate(file_get_contents($manifestFile));
    }

    /**
     * @param string $folder Path to dynamic folder
     * @return void
     */
    private function generateDynamicMountEntry(string $folder): void
    {
        $files = array_diff(scandir($folder), ['.', '..', 'entry.js']);
        $entryContent = '';

        foreach ($files as $file) {
            $file = str_replace('.js', '', $file);
            $entryContent .= "import './$file';\n";
        }

        file_put_contents($folder . '/entry.js', $entryContent);
    }

    /**
     * @param string $filename
     * @param string $id
     * @return void
     */
    public function requireDynamicMount(string $filename, string $id): void
    {
        $viteFolder = Option::get('zeantar.vite', 'VITE_LOCATION');
        $dynamicFolder = $viteFolder . '/src/dynamic/mount';

        if (!file_exists($dynamicFolder)) {
            mkdir($dynamicFolder);
        }

        $dynamicMount = new ApplicationMount($filename, $id);

        $unique = $dynamicMount->unique;
        $path = $dynamicFolder . '/' . $unique . '.js'; 

        if (file_exists($path)) {
            return;
        }

        file_put_contents($path, (string) $dynamicMount);
        $this->generateDynamicMountEntry($dynamicFolder);
    }

    /**
     * @param string $content
     * @return string Unique identifier
     */
    public function requireDynamicApplication(string $content): string
    {
        $viteFolder = Option::get('zeantar.vite', 'VITE_LOCATION');
        $dynamicFolder = $viteFolder . '/src/dynamic/application';

        if (!file_exists($dynamicFolder)) {
            mkdir($dynamicFolder);
        }

        $dynamicApplication = new Application($content);
        
        $unique = $dynamicApplication->unique;
        $path = $dynamicFolder . '/' . $unique . '.vue';

        if (file_exists($path)) {
            return $unique;
        }

        file_put_contents($path, (string) $dynamicApplication);
        return $unique;
    }

    public function getHead(?string $manifestFile = null): string
    {
        if (!self::isEnabled()) {
            return '';
        }
    
        $debugEnabled = self::isDebugEnabled();

        $manifestLocation = $this->locateManifest($manifestFile);
        if (is_null($manifestLocation)) {
            if ($debugEnabled) {
                throw new ManifestLocationException("
                    Unable to locate manifest at: \"$manifestLocation\""
                );
            }
            return '';
        }

        try {
            $manifest = new Manifest($manifestLocation);
        } catch (InvalidManifestException $e) {
            if ($debugEnabled) {
                throw $e;
            }
            return '';
        }

        $content = '';

        foreach ($manifest->files as $file) {
            /** @var AssetFile $file */

            $relAssetPath = str_replace(
                $_SERVER['DOCUMENT_ROOT'],
                '',
                $this->viteFolder . 'dist/'
            );

            if ($debugEnabled) {
                $content .= '<!-- ' . $file->source . '-->';
            }

            if ($file->isEntry) {
                $content .= "<script type=\"module\" src=\"$relAssetPath{$file->filename}\"></script>";
            }

            foreach ($file->css as $cssFile) {
                $content .= "<link rel=\"stylesheet\" href=\"$relAssetPath$cssFile\"/>";
            }
        }

        return $content;
    }

    public function showHead(): void
    {
        $head = $this->getHead();
        if (self::isInjected()) {
            return;
        }
        if (!empty($head)) {
            $this->setInjected(true);
        }
        echo $head;
    }
}