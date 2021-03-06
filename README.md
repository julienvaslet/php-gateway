# php-gateway
> Make a REST API development easy with PHP 7.4+

## Presentation
This module's goal is to let you focus on your API development while writing clean code.
It uses, and requires, typing and documentation to correctly work.

For now, there is no production optimizations but they are planned.


## Example
The following examples are extracts of the `api.php` file.

### SerializableObject example
Serializable objects can be used for object output or input in your API. Here is a definition example:

```php
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
```

### Route definition
Here is a route to list the cars on `GET` request on the `/car` API path. The real path will be `/api/v1/car` because we
don't override the version parameter in the route and the `/api` prefix is defined in the router in the `api.php` file.

This route take multiple optional parameters and will return a list of the `Car` serializable object defined before.

```php
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

    // ...
}
```

### SerializableObject as input data
SerializableObject can be used as input data simply by specifying them as input parameter. As it is serializable, it is
also considered like a valid `Response`.

```php
class CarRoute extends Route
{
    protected static string $path = "/car";

    // ...

    /**
     * Create a car.
     *
     * @param Car $car  The new car's data.
     */
    public function put(Car $car) : Response
    {
        // Do not really create the car.
        return $car;
    }
}
```

### Parameters in route path
Variable parameter can also be specified in the route path with the `{name}` special sequence. They must be defined
as a class attribute of the route.

```php
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
```

### Auto-generated documentation
The module has a predefined unmapped route `ApiDocumentationRoute` which auto generate an HTML documentation of the API.
You can use it by extending it and mapping it to a path. You can also easily override how the output is formatted.

```php
class DocRoute extends ApiDocumentationRoute
{
    protected static string $path = "/";
}
```

### Trigger the routing
To give life to your API, call the `handleRequest` static method of the `Router` class where you want.

```php
Router::handleRequest("/api", 1);
```