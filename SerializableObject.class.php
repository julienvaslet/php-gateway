<?php
// Copyright (c) 2020 Julien Vaslet

namespace gateway;

require_once(__DIR__."/Serializable.interface.php");


abstract class SerializableObject implements Serializable
{
    public static function getSerializableAttributes() : array
    {
        $attributes = array();
        $reflectionClass = new \ReflectionClass(static::class);

        foreach ($reflectionClass->getProperties() as $reflectionProperty)
        {
            $attributes[$reflectionProperty->getName()] = $reflectionProperty->getType();
        }

        return $attributes;
    }

    public final function serialize() : array
    {
        $data = array();
        $attributes = array_keys(self::getSerializableAttributes());

        foreach ($this as $attribute => $value)
        {
            if (in_array($attribute, $attributes))
            {
                $data[$attribute] = static::serializeData($value);
            }
        }

        return $data;
    }

    public function serializeResponse() : string
    {
        return json_encode($this->serialize());
    }

    public static final function serializeData($data)
    {
        if ($data instanceof Serializable)
        {
            $data = $data->serialize();
        }
        else if (is_array($data))
        {
            $data = array_map(array(static::class, "serializeData"), $data);
        }

        return $data;
    }

    public static final function deserialize(array $data) : Serializable
    {
        $attributes = static::getSerializableAttributes();
        $instance = (new \ReflectionClass(static::class))->newInstanceWithoutConstructor();

        foreach ($attributes as $attribute => $type)
        {
            if (array_key_exists($attribute, $data))
            {
                $value = $data[$attribute];

                if (!is_null($type))
                {
                    $value = Route::castValue($type, $value);
                }

                $instance->$attribute = $value;
            }
        }

        return $instance;
    }
}