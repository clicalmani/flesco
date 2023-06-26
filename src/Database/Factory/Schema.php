<?php
namespace Clicalmani\Flesco\Database\Factory;

class Schema
{
    static function create($table, $callback)
    {
        $maker = new Maker($table);

        $callback($maker);

        $maker->make();
    }
}