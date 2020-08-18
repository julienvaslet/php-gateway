<?php
// Copyright (c) 2020 Julien Vaslet

namespace gateway;

require_once(__DIR__."/responses/Response.interface.php");
use \gateway\responses\Response;


interface Serializable extends Response
{
    /**
     * Return serializable attributes of the class as an array of strings.
     */
    public static function getSerializableAttributes() : array;

    /**
     * Return the serialized instance as an associative array, where the
     * keys are the attribute and the values their respecting values.
     */
    public function serialize() : array;

    /**
     * Return the serialized instance as a serialized Response.
     */
    public function serializeResponse() : string;

    /**
     * Return the serialized value for $data.
     */
    public static function serializeData($data);

    /**
     * Return an instance from an associative array.
     */
    public static function deserialize(array $data) : Serializable;
}