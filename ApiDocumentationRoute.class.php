<?php
// Copyright (c) 2020 Julien Vaslet

namespace gateway;

require_once(__DIR__."/Route.class.php");
require_once(__DIR__."/SerializableObject.class.php");
require_once(__DIR__."/responses/Response.class.php");
require_once(__DIR__."/responses/HtmlResponse.class.php");

use \gateway\responses\Response;
use \gateway\responses\HtmlResponse;


abstract class ApiDocumentationRoute extends Route
{
    protected $title;
    protected $stylesheetUrl;

    public function __construct()
    {
        $this->title = "API Documentation";
        $this->stylesheetUrl = null;
    }

    protected function getRoutes() : array
    {
        $definitions = array();
        $router = new Router(static::$version);

        foreach ($router->getRoutes() as $routeClass)
        {
            $definition = $routeClass::getRouteDefinition();
            $definitions[$definition["path"]] = $definition;
        }

        return $definitions;
    }

    private static function addCssRule(array &$rules, string $target, string $key, string $value) : void
    {
        if (!array_key_exists($target, $rules))
        {
            $rules[$target] = array();
        }

        $rules[$target][$key] = $value;
    }

    protected function generateHTML(array $routes, string $stylesheetUrl = null) : string
    {
        // Static HTML formatting, this method is meant to be overriden
        // to let you use your favorite templating system.
        $output = '<!doctype html><html lang="en"><head><meta charset="utf8"><title>'.htmlentities($this->title).'</title>';

        // Rules are dynamically built to be more maintainable
        $css = array();
        static::addCSSRule($css, "html", "font-family", "sans-serif");
        static::addCSSRule($css, "html", "font-size", "14px");
        static::addCSSRule($css, "h1", "font-size", "2em");
        static::addCSSRule($css, "h2", "font-size", "1.8em");
        static::addCSSRule($css, "h2", "margin-top", "1.8em");
        static::addCSSRule($css, "h3", "font-size", "1.6em");
        static::addCSSRule($css, "h4", "font-size", "1.4em");
        static::addCSSRule($css, "h4", "margin-bottom", "1.2em");
        static::addCSSRule($css, "h5", "font-size", "1.2em");
        static::addCSSRule($css, "h5", "margin-bottom", "1em");
        static::addCSSRule($css, "h6", "font-size", "1em");
        static::addCSSRule($css, "div.method", "margin-top", "1.4em");
        static::addCSSRule($css, "div.head>*", "display", "inline");
        static::addCSSRule($css, "div.head>h3", "font-family", "monospace");
        static::addCSSRule($css, "div.head>h3", "white-space", "pre");
        static::addCSSRule($css, "div.head>p.description", "margin-left", "1em");
        static::addCSSRule($css, "div.head>p.description", "font-family", "1.2em");
        static::addCSSRule($css, "div.parameter", "margin", "0.25em 0");
        static::addCSSRule($css, "div.parameter>*", "display", "inline-block");
        static::addCSSRule($css, "div.parameter>*", "width", "12.5%");
        static::addCSSRule($css, "div.parameter>*", "margin", "0");
        static::addCSSRule($css, "div.parameter>p.description", "width", "75%");
        static::addCSSRule($css, "div.parameter.required>p.type::after", "content", '" (required)"');
        static::addCSSRule($css, "span.default-value::before", "content", '" (default: "');
        static::addCSSRule($css, "span.default-value::after", "content", '")"');

        $output .= '<style type="text/css">';
        foreach ($css as $target => $rules)
        {
            $output .= "${target}{".implode(";", array_map(function($k, $v){ return "${k}:${v}"; }, array_keys($rules), array_values($rules)))."}";
        }
        $output .= "</style>";

        if (!is_null($stylesheetUrl))
        {
            $output .= '<link rel="stylesheet" type="text/css" href="'.$stylesheetUrl.'">';
        }

        $output .= '</head><body>';
        $output .= "<h1>".htmlentities($this->title)."</h1>";
        $output .= "<h2>Routes</h2>";

        foreach ($routes as $route => $routeDefinition)
        {
            $output .= "<h3>".htmlentities($route)."</h3>";

            foreach ($routeDefinition["methods"] as $method => $methodDefinition)
            {
                $method = strtoupper($method);
                $output .= '<div class="method '.htmlentities($method).'">';
                $output .= '<div class="head">';
                $output .= '<h4>'.htmlentities($method).' <span class="location">'.htmlentities($route).'</span></h4>';
                $output .= '<p class="description">'.htmlentities($methodDefinition["description"]).'</p>';
                $output .= "</div>";
                $output .= '<div class="body">';
                $output .= "<h5>Parameters</h5>";

                if (!count($methodDefinition["parameters"]))
                {
                    $output .= '<p class="empty">No parameters</p>';
                }
                else
                {
                    foreach ($methodDefinition["parameters"] as $parameter => $parameterDefinition)
                    {
                        $output .= '<div class="parameter'.($parameterDefinition["required"] ? ' required' : '').'">';
                        $output .= "<h6>".htmlentities($parameter)."</h6>";

                        if (!is_null($parameterDefinition["type"]))
                        {
                            $output .= '<p class="type">'.htmlentities($parameterDefinition["type"]);

                            if (!is_null($parameterDefinition["default"]))
                            {
                                $output .= '<span class="default-value">'.htmlentities($parameterDefinition["default"]).'</span>';
                            }

                            $output .= "</p>";
                        }

                        $output .= '<p class="description">'.htmlentities($parameterDefinition["description"]).'</p>';
                        $output .= "</div>";
                    }
                }

                $output .= "</div>";
                $output .= "</div>";
            }
        }

        $serializableObjects = array();

        foreach (get_declared_classes() as $class)
        {
            if (is_subclass_of($class, "\gateway\SerializableObject"))
            {
                $serializableObjects[] = $class;
            }
        }

        if (count($serializableObjects) > 0)
        {
            $output .= "<h2>Types</h2>";

            foreach ($serializableObjects as $class)
            {
                $output .= "<h3>".htmlentities($class)."</h3>";

                foreach ($class::getSerializableAttributes() as $parameter => $type)
                {
                    $output .= '<div class="parameter">';
                    $output .= "<h6>".htmlentities($parameter)."</h6>";
                    $output .= '<p class="type">'.htmlentities($type).'</p>';
                    $output .= '<p class="description"></p>';
                    $output .= "</div>";
                }
            }
        }

        $output .= "</body></html>";

        return $output;
    }

    /**
     * Shows this documentation page.
     */
    public function get() : Response
    {
        return new HtmlResponse($this->generateHTML($this->getRoutes(), $this->stylesheetUrl));
    }
}
