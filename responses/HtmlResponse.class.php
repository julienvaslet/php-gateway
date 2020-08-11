<?php
// Copyright (c) 2020 Julien Vaslet

namespace gateway\responses;

require_once(__DIR__."/Response.class.php");


class HtmlResponse extends Response
{
    protected string $content;

    public function __construct(string $content)
    {
        parent::__construct();
        $this->content = $content;
    }

    public function serializeResponse() : string
    {
        return $this->content;
    }
}
