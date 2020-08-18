<?php
// Copyright (c) 2020 Julien Vaslet

namespace gateway\responses;


interface Response
{
    public function serializeResponse() : string;
}
