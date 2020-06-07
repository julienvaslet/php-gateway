<?php
// Copyright (c) 2020 Julien Vaslet

namespace gateway\exceptions;

require_once(__DIR__."/Exception.class.php");


class InternalServerError extends Exception
{
    public function __construct(string $message = "An internal server error occured.", ?\Exception $previous = null)
    {
        parent::__construct($message, 500, $previous);
    }
}
