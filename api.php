<?php

/*
 * This is a sample usage of the gateway module.
 *
 * It is brought to be used with the following Apache configuration:
 * ```
 * RewriteEngine On
 * RewriteRule ^api/v[0-9](/.*)?$ api.php
 * ```
 * This configuration is written in the `.htaccess` file of this depot.
 *
 * There is no dynamic update of the dataset, the car creation
 * route is only there for the example.
 */

require_once("gateway/gateway.php");
use \gateway\ApiDocumentationRoute;
use \gateway\SerializableObject;
use \gateway\Route;
use \gateway\Router;
use \gateway\exceptions\NotFoundException;
use \gateway\responses\Response;
use \gateway\responses\JsonResponse;


class Car extends SerializableObject
{
    protected int $id;
    protected string $brand;
    protected float $price;

    public function __construct(int $id, string $brand, float $price)
    {
        $this->id = $id;
        $this->brand = $brand;
        $this->price = $price;
    }

    public function getBrand() : string
    {
        return $this->brand;
    }

    public function getPrice() : float
    {
        return $this->price;
    }
}


$cars = array(
    1 => new Car(1, "Ford", 45000.0),
    2 => new Car(2, "Toyota", 35000.0),
    3 => new Car(3, "Chevrolet", 20000.0)
);


class CarRoute extends Route
{
    protected static string $path = "/car";

    /**
     * List all the cars.
     *
     * @param int $minPrice     The minimal price of the returned cars.
     * @param int $maxPrice     The maximal price of the returned cars.
     * @param string $brand     The brand name of the returned cars.
     */
    public function get(int $minPrice = 0, ?int $maxPrice = null, ?string $brand = null) : Response
    {
        global $cars;
        $filteredCars = array();

        foreach ($cars as $id => $car)
        {
            if ($car->getPrice() >= $minPrice && (is_null($maxPrice) || $car->getPrice() <= $maxPrice))
            {
                if (is_null($brand) || $car->getBrand() == $brand)
                {
                    $filteredCars[] = $car;
                }
            }
        }

        return new JsonResponse(
            array(
                "result" => $filteredCars
            )
        );
    }

    /**
     * Create a car.
     *
     * @param Car $object  The new car's data.
     */
    public function put(Car $car) : Response
    {
        // Do not really create the car.
        return $car;
    }
}


class CarInfoRoute extends Route
{
    protected static string $path = "/car/{id}";
    protected int $id;

    /**
     * @param int $id   The car identifier.
     */
    public function __construct(int $id) {
        $this->id = $id;
    }

    /**
     * Get a specific car.
     */
    public function get() : Response
    {
        global $cars;

        if (!array_key_exists($this->id, $cars))
        {
            throw new NotFoundException();
        }

        return $cars[$this->id];
    }
}


// Map the documentation to a route
class DocRoute extends ApiDocumentationRoute
{
    protected static string $path = "/";
}

Router::handleRequest("/api", 1);
