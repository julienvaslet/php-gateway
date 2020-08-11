<?php
// Copyright (c) 2020 Julien Vaslet

namespace gateway\exceptions;

require_once(__DIR__."/Exception.class.php");


class NotFoundException extends Exception
{
    function __construct(\Exception $previous = null)
    {
        parent::__construct("Not found.", 404, $previous);
    }
}