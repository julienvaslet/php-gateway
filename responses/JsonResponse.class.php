<?php
// Copyright (c) 2020 Julien Vaslet

namespace gateway\responses;

require_once(__DIR__."/Response.class.php");
require_once(__DIR__."/../SerializableObject.class.php");

use \gateway\SerializableObject;


class JsonResponse extends Response
{
    protected array $data;

    public function __construct(array $data)
    {
        parent::__construct();
        $this->data = $data;
    }

    public function serializeResponse() : string
    {
        return json_encode(
            SerializableObject::serializeData(
                $this->data
            )
        );
    }
}