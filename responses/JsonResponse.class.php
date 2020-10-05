<?php
// Copyright (c) 2020 Julien Vaslet

namespace gateway\responses;

require_once(__DIR__."/Response.interface.php");
require_once(__DIR__."/../Serializable.interface.php");

use \gateway\Serializable;


class JsonResponse implements Response
{
    protected array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function serializeResponse() : string
    {
        return json_encode(
            static::serializeData(
                $this->data
            )
        );
    }

    protected static function serializeData($data)
    {
        if ($data instanceof Serializable)
        {
            $data = $data->serialize();
        }
        else if ($data instanceof \DateTime)
        {
            $data = $data->format(\DateTimeInterface::ISO8601);
        }
        else if (is_array($data))
        {
            $data = array_map(array(static::class, "serializeData"), $data);
        }

        return $data;
    }
}