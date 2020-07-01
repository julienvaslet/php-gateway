<?php
// Copyright (c) 2020 Julien Vaslet

namespace gateway;

require_once(__DIR__."/SerializableObject.class.php");


abstract class Route
{
    public const HTTP_METHODS = array("get", "post", "put", "patch");
    protected static int $version = 1;
    protected static string $path = "";

    public function __construct()
    {
    }

    /*
     * These methods are commented to authorize child classes
     * to have non optional arguments without a PHP warning.
     * These methods are resolved by reflection in the Router.
     */
    // public function get() : Response
    // public function post() : Response
    // public function put() : Response
    // public function patch() : Response

    public static function getPath() : string
    {
        return static::$path;
    }

    public static function getVersion() : int
    {
        return static::$version;
    }

    public static function getRouteDefinition() : array
    {
        return array(
            "path" => static::getPath(),
            "methods" => static::getMethods()
        );
    }

    protected static function getMethods() : array
    {
        $methods = array();
        $reflectionClass = new \ReflectionClass(static::class);

        // Retrieve the path variables, common to every method.
        $constructorDefinition = static::getMethodDefinition($reflectionClass->getConstructor());

        // Add a special `pathParameter` variable to constructor parameters.
        foreach (array_keys($constructorDefinition["parameters"]) as $parameterName)
        {
            $constructorDefinition["parameters"][$parameterName]["pathParameter"] = true;
        }

        foreach (static::HTTP_METHODS as $httpMethod)
        {
            if ($reflectionClass->hasMethod($httpMethod))
            {
                $reflectionMethod = $reflectionClass->getMethod($httpMethod);
                $methods[$httpMethod] = static::getMethodDefinition($reflectionMethod, $constructorDefinition["parameters"]);
            }
        }

        return $methods;
    }

    protected static function getMethodDefinition(\ReflectionMethod $reflectionMethod, array $pathParameters = array()) : array
    {
        $descriptions = static::getMethodDescriptions($reflectionMethod);

        $definition = array(
            "name" => $reflectionMethod->getName(),
            "description" => $descriptions[""],
            "parameters" => $pathParameters
        );

        foreach ($reflectionMethod->getParameters() as $reflectionParameter)
        {
            $parameterName = $reflectionParameter->getName();

            if (array_key_exists($parameterName, $pathParameters))
            {
                throw new \Exception("Bad route definition: parameter \"${parameterName}\" is defined both in the path and in the method.");
            }

            $parameter = array(
                "type" => null,
                "required" => true,
                "default" => null,
                "description" => $descriptions[$reflectionParameter->getName()] ?? ""
            );

            if ($reflectionParameter->hasType())
            {
                $parameter["type"] = $reflectionParameter->getType()->getName();
            }

            if ($reflectionParameter->isDefaultValueAvailable())
            {
                $parameter["required"] = false;
                $parameter["default"] = $reflectionParameter->getDefaultValue();
            }

            $definition["parameters"][$parameterName] = $parameter;
        }

        return $definition;
    }

    protected static function getMethodDescriptions(\ReflectionMethod $method) : array
    {
        $description = array("" => "");
        $comment = $method->getDocComment();

        // Early exit if there is no doc-comment.
        if ($comment === false)
        {
            return $description;
        }

        $lines = array_map(function($line){ return trim($line, " */\t\0\x0B"); }, preg_split("/\r?\n|\r/", $comment));
        $currentElement = "";

        foreach($lines as $line)
        {
            $content = $line;

            if (\preg_match('/@param(?:\s+[^\s]+)?\s+(?:\$(?P<parameter>[^\s]+))(?P<description>.*)$/', $content, $match))
            {
                $currentElement = $match["parameter"];
                $content = $match["description"];
            }

            if (!array_key_exists($currentElement, $description))
            {
                $description[$currentElement] = "";
            }
            else if (strlen($description[$currentElement]) > 0)
            {
                $description[$currentElement] .= " ";
            }

            $description[$currentElement] .= trim($content);
        }

        return $description;
    }

    protected static function patternizeParameter(array $matches) : string
    {
        $parameterName = $matches[1];

        $reflectionClass = new \ReflectionClass(static::class);
        $constructorDefinition = static::getMethodDefinition($reflectionClass->getConstructor());

        if (!array_key_exists($parameterName, $constructorDefinition["parameters"]))
        {
            throw new \Exception("Bad path definition, parameter \"${parameterName}\" is not defined in the route.");
        }

        $parameterType = $constructorDefinition["parameters"][$parameterName]["type"];

        // No type check if the parameter is not typed
        return "(?P<${parameterName}>".(is_null($parameterType) ? "[^/]+" : static::getTypePattern($parameterType)).")";
    }

    public static function getPatternizedPath() : string
    {
        $path = static::getPath();
        $path = preg_replace_callback("/{([^}]+)}/", array(static::class, "patternizeParameter"), $path);

        return "|^".$path."\$|";
    }

    public static function createFromPath(string $path) : Route
    {
        if (!\preg_match(static::getPatternizedPath(), $path, $matches))
        {
            throw new \Exception("Path does not match the route.");
        }

        $reflectionClass = new \ReflectionClass(static::class);
        $reflectionMethod = $reflectionClass->getConstructor();
        $reflectionParameters = $reflectionMethod->getParameters();

        // TODO: This should be baked before
        $constructorParameters = array();
        foreach ($reflectionParameters as $reflectionParameter)
        {
            $constructorParameters[$reflectionParameter->getName()] = $reflectionParameter->hasType() ? $reflectionParameter->getType()->getName() : null;
        }

        $parameters = array();
        foreach ($constructorParameters as $parameterName => $parameterType)
        {
            if (!array_key_exists($parameterName, $matches)) {
                throw new \Exception("Path definition is not complete: missing \"${parameterName}\" parameter.");
            }

            $parameters[] = !is_null($parameterType) ? static::castValue($parameterType, $matches[$parameterName]) : $matches[$parameterName];
        }

        return $reflectionClass->newInstanceArgs($parameters);
    }

    public static function getTypePattern(string $type, bool $withDelimiters = false) : string
    {
        $patterns = array(
            "int" => "[-+]?[0-9]+",
            "float" => "[-+]?[0-9]+(?:\.[0-9]+)?",
            "bool" => "(?:0|1|true|false)"
        );

        if (!array_key_exists($type, $patterns))
        {
            throw new Exception("No pattern defined for type \"${type}\".");
        }

        return $withDelimiters ? "/^".$patterns[$type]."\$/".($type == "bool" ? "i": "") : $patterns[$type];
    }

    public static function castValue(string $type, $value)
    {
        switch ($type)
        {
            case "int":
            {
                if (preg_match(static::getTypePattern($type, true), $value))
                {
                    $value = intval($value);
                }
                else
                {
                    throw new \UnexpectedValueException("Value \"${value}\" is not an integer.");
                }

                break;
            }

            case "float":
            {
                if (preg_match(static::getTypePattern($type, true), $value))
                {
                    $value = floatval($value);
                }
                else
                {
                    throw new \UnexpectedValueException("Value \"${value}\" is not a float.");
                }

                break;
            }

            case "bool":
            {
                if (preg_match(static::getTypePattern($type, true), $value))
                {
                    $value = in_array(strtolower($value), array("1", "true"));
                }
                else
                {
                    throw new \UnexpectedValueException("Value \"${value}\" is not a boolean.");
                }

                break;
            }

            default:
            {
                if (is_subclass_of($type, "\gateway\SerializableObject"))
                {
                    $value = $type::deserialize($value);
                }

                break;
            }
        }

        return $value;
    }
}