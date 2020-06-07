<?php
// Copyright (c) 2020 Julien Vaslet

namespace gateway\exceptions;
// TODO: Check imports


class Exception extends \Exception
{
    public function __construct(string $message, int $httpCode = 500, ?\Exception $previous = null)
    {
        parent::__construct($message, $httpCode, $previous);
    }

    public function toArray() : array
    {
        $errors = array($this->message);
        $previous  = $this->getPrevious();

        while (!is_null($previous))
        {
            $errors[] = $previous->getMessage();
            $previous = $previous->getPrevious();
        }

        return array("errors" => $errors);
    }

    public function toJsonResponse() : \gateway\responses\JsonResponse
    {
        return new JsonResponse($this->toArray(), false);
    }
}
