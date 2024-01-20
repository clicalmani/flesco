<?php 
namespace Clicalmani\Flesco\Support;

use Dotenv\Dotenv;
use Dotenv\Repository\Adapter\PutenvAdapter;
use Dotenv\Repository\RepositoryBuilder;

global $dotenv;

/**
 * Class Env
 * 
 * @package Clicalmani\Flesco
 * @author @clicalmani
 */
class Env 
{
    /**
     * Indicate if the putenv adapter is enabled.
     * 
     * @var bool
     */
    protected static $putenv = true;

    /**
     * The environment repository instance.
     * 
     * @var \Dotenv\Repository\RepositoryInterface|null
     */
    protected static $repository;

    /**
     * Enable the putenv adapter
     * 
     * @return void
     */
    public static function enablePutenv() : void
    {
        static::$putenv = true;
        static::$repository = null;
    }

    /**
     * Disable the putenv adapter
     * 
     * @return void
     */
    public static function disablePutenv() : void
    {
        static::$putenv = false;
        static::$repository = null;
    }

    /**
     * Gets the environment repository instance.
     * 
     * @return \Dotenv\Repository\RepositoryInterface
     */
    public static function getRepository()
    {
        if (static::$repository === null) {
            $builder = RepositoryBuilder::createWithDefaultAdapters();

            if (static::$putenv) {
                $builder = $builder->addAdapter(PutenvAdapter::class);
            }

            static::$repository = $builder->immutable()->make();
        }
        
        return static::$repository;
    }
}
