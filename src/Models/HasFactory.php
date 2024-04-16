<?php 
namespace Clicalmani\Flesco\Models;

trait HasFactory
{
    /**
     * @template T
     */
    public static function seed()
    {
        $className = static::getClassName();
        $model = substr($className, strrpos($className, "\\") + 1);

        /** @param class-string<T> */
        $factory = "\\Database\\Factories\\" . $model . 'Factory';
        
        return $factory::new();
    }
}
