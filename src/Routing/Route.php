<?php
namespace Clicalmani\Flesco\Routing;

use Clicalmani\Flesco\Support\Facades\Facade;

/**
 * @method static string[] all()
 * @method static bool isApi()
 * @method static string getClientVerb()
 * @method static string current()
 * @method static \Clicalmani\Routing\Group|null group(mixed ...$parameters)
 * @method static void pattern(string $param, string $pattern)
 * @method static \Clicalmani\Routing\Validator|\Clicalmani\Routing\Group register(string $method, string $route, mixed $callback, ?bool $bind = true)
 * @method static \Clicalmani\Routing\Group controller(string $class)
 */
class Route extends Facade
{}
