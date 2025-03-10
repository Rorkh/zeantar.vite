<?php

namespace Zeantar\Vite\Dynamic;

final class Application
{
    /**
     * Unique identifier
     *
     * @var string
     */
    public string $unique;

    /**
     * @param string $content
     */
    public function __construct(private readonly string $content)
    {
        // TODO: Normal unique generator
        $this->unique = preg_replace('/[0-9]+/', '', md5($content));
    }

    public function __tostring(): string
    {
        return $this->content;
    }
}