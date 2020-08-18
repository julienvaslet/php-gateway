<?php
// Copyright (c) 2020 Julien Vaslet

namespace gateway\responses;

require_once(__DIR__."/Response.interface.php");


class HtmlResponse implements Response
{
    protected string $content;

    public function __construct(string $content)
    {
        $this->content = $content;
    }

    public function serializeResponse() : string
    {
        return $this->content;
    }
}
