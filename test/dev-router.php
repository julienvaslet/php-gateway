<?php
/*
 * Router for embedded PHP webserver.
 */

if (preg_match("|^/api/v[0-9](?:/.*)?|", $_SERVER["REQUEST_URI"]))
{
    require_once("api.php");
}
else
{
    return false;
}