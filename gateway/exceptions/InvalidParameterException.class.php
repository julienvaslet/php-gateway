<?php
// Copyright (c) 2020 Julien Vaslet

namespace gateway\exceptions;

require_once(__DIR__."/Exception.class.php");


class InvalidParameterException extends Exception
{
    function __construct(string $name, \Exception $previous = null, string $type = null)
    {
        $message = is_null($type)
            ? "Parameter \"${name}\" value is invalid."
            : "Parameter \"${name}\" value is not a valid ${type}.";

        parent::__construct($message, 400, $previous);
    }
}
