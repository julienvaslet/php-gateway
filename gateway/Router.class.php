<?php
// Copyright (c) 2020 Julien Vaslet

namespace gateway;

require_once(__DIR__."/Route.class.php");
require_once(__DIR__."/exceptions/Exception.class.php");
require_once(__DIR__."/exceptions/InternalServerError.class.php");
require_once(__DIR__."/exceptions/InvalidParameterException.class.php");
require_once(__DIR__."/exceptions/InvalidParameterTypeException.class.php");
require_once(__DIR__."/exceptions/MethodNotAllowedException.class.php");
require_once(__DIR__."/exceptions/MissingParameterException.class.php");
require_once(__DIR__."/exceptions/NotFoundException.class.php");

use \gateway\exceptions\Exception;
use \gateway\exceptions\InternalServerError;
use \gateway\exceptions\InvalidParameterException;
use \gateway\exceptions\InvalidParameterTypeException;
use \gateway\exceptions\MethodNotAllowedException;
use \gateway\exceptions\MissingParameterException;
use \gateway\exceptions\NotFoundException;


class Router
{
    protected $routes;

    public function __construct(int $version = 1)
    {
        $this->routes = array();
        foreach (\get_declared_classes() as $class)
        {
            $reflectionClass = new \ReflectionClass($class);
            if (!$reflectionClass->isAbstract() && $reflectionClass->isSubclassOf(Route::class) && $class::getVersion() == $version)
            {
                $this->routes[$class::getPatternizedPath()] = $class;
            }
        }
    }

    public function getRoutes() : array
    {
        return $this->routes;
    }

    public function handle(string $method, string $path, array $parameters = array())  // : array|string
    {
        $method = strtolower($method);
        $route = $this->findRoute($path);

        if (is_null($route))
        {
            throw new NotFoundException();
        }

        $params = array();
        $routeClass = get_class($route);
        $routeDefinition = $routeClass::getRouteDefinition();

        if (!array_key_exists($method, $routeDefinition["methods"]))
        {
            throw new MethodNotAllowedException();
        }

        $methodDefinition = $routeDefinition["methods"][$method];

        foreach ($methodDefinition["parameters"] as $parameterName => $parameterDefinition)
        {
            // Ignore parameter from paths as they are already defined in path.
            if (array_key_exists("pathParameter", $parameterDefinition) && $parameterDefinition["pathParameter"])
            {
                continue;
            }

            $value = array_key_exists($parameterName, $parameters) ? $parameters[$parameterName] : null;

            if (is_null($value))
            {
                if ($parameterDefinition["required"] == true)
                {
                    throw new MissingParameterException($parameterName);
                }

                // If the parameter is not required, it has a default value.
                $value = $parameterDefinition["default"];
            }
            else if (!is_null($parameterDefinition["type"]))
            {
                try
                {
                    $value = Route::castValue($parameterDefinition["type"], $value);
                }
                catch (\Exception $exception)
                {
                    throw new InvalidParameterTypeException($parameterName, $parameterDefinition["type"]);
                }
            }

            $params[] = $value;
        }

        return $route->$method(...$params);
    }

    protected function findRoute(string $path) : Route
    {
        $routeInstance = null;

        foreach ($this->routes as $routePath => $route)
        {
            if (\preg_match($routePath, $path))
            {
                $routeInstance = $route::createFromPath($path);
                break;
            }
        }

        return $routeInstance;
    }

    public static function handleRequest($apiPrefix = "/api", $apiDefaultVersion = 1) : void
    {
        $apiVersion = $apiDefaultVersion;
        $path = $_SERVER["REQUEST_URI"];

        if (preg_match('/^'.preg_quote($apiPrefix, "/").'\/v(?P<version>[0-9]+)(?P<uri>\/[^?]*)/', $path, $matches))
        {
            $apiVersion = intval($matches["version"]);
            $path = $matches["uri"];
        }

        $router = new Router($apiVersion);
        $response = null;

        try
        {
            $response = $router->handle($_SERVER["REQUEST_METHOD"], $path, array_merge($_GET, $_POST));
        }
        catch (Exception $exception)
        {
            http_response_code($exception->getCode());
            $response = $exception->toJsonResponse();
        }
        catch (\Exception $exception)
        {
            $exception = new InternalServerError("An internal server error occured", $exception);
            http_response_code($exception->getCode());
            $response = $exception->toJsonResponse();
        }

        echo $response->serializeResponse();
    }
}