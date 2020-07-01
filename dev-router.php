<?php
/*
 * Router.
 */

if (preg_match("|^/api/v([0-9])/(.*)|", $_SERVER["REQUEST_URI"], $matches))
{
    require_once("api.php");
}
else
{
    return false;
}