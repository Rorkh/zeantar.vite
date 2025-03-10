<?php

namespace Zeantar\Vite\Dynamic;

final class ApplicationMount
{
    /**
     * Unique identifier
     *
     * @var string
     */
    public string $unique;

    /**
     * @param string $filename
     * @param string $id
     */
    public function __construct(
        private readonly string $filename,
        private readonly string $id
    )
    {
        // TODO: Normal unique generator
        $this->unique = preg_replace('/[0-9]+/', '', md5($filename . $id));
    }

    public function __tostring(): string
    {
        $content = "// Auto generated file. Do not modify.\n";
        $content .= '// ' . date('F j, Y, g:i a') . "\n"; 

        $content .= "import { createApp } from 'vue'\n";
        $content .= "import App from '{$this->filename}'\n\n";

        $content .= "if (document.getElementById('{$this->id}')) {\n";
        $content .= "\tconst {$this->unique} = createApp(App);\n";
        $content .= "\t{$this->unique}.mount('#{$this->id}');\n";
        $content .= "}";

        return $content;
    }
}