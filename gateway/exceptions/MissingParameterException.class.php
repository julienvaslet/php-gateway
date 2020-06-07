<?php
// Copyright (c) 2020 Julien Vaslet

namespace gateway\exceptions;

require_once(__DIR__."/Exception.class.php");


class MissingParameterException extends Exception
{
    function __construct(string $name, \Exception $previous = null)
    {
        parent::__construct("Parameter \"${name}\" is required for this request.", 400, $previous);
    }
}
