<?php
// Copyright (c) 2020 Julien Vaslet

namespace gateway\responses;


abstract class Response
{
    public function __construct()
    {
    }

    public abstract function serializeResponse() : string;
}
