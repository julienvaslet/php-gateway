<?php
// Copyright (c) 2020 Julien Vaslet

namespace gateway\exceptions;

require_once(__DIR__."/Exception.class.php");


class MethodNotAllowedException extends Exception
{
    function __construct(\Exception $previous = null)
    {
        parent::__construct("Method not allowed.", 405, $previous);
    }
}
