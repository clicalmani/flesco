<?php 
namespace Clicalmani\Flesco\Facade;

class Storage extends Facade 
{
    /**
     * Create storage symbolic link in the public directory
     * 
     * @return bool True on success, false on failure
     */
    public static function link()
    {
        return Tonka::link(storage_path(), root_path('public'));
    }
}
