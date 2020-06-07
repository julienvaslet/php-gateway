<?php
// Copyright (c) 2020 Julien Vaslet

namespace gateway\exceptions;

require_once(__DIR__."/Exception.class.php");


class InvalidParameterTypeException extends InvalidParameterException
{
    function __construct(string $name, string $type, \Exception $previous = null)
    {
        parent::__construct($name, $previous, $type);
    }
}
