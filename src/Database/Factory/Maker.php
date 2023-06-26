<?php
namespace Clicalmani\Flesco\Database\Factory;

use Clicalmani\Flesco\Database\DBQuery;
use Clicalmani\Flesco\Database\Factory\Column;
use Clicalmani\Flesco\Database\Factory\Indexes\Index;

class Maker
{
    private $query;
    private $columns = [];
    private $indexes = [];
    private $primary;

    function __construct($table) 
    {
        $this->query = new DBQuery;
        $this->query->set('type', DB_QUERY_CREATE);
        $this->query->set('table', $table);
    }

    function column($name)
    {
        $column = new Column($name);
        $this->columns[] = $column;

        return $column;
    }

    function index($name)
    {
        $index = new Index($name);
        $this->indexes[] = $index;
        return $index;
    }

    function engine($engine = 'InnoDB')
    {
        $this->query->set('engine', $engine);
    }

    function collate($default_collation)
    {
        $this->query->set('collate', $default_collation);
    }

    function charset($default_charset)
    {
        $this->query->set('charset', $default_charset);
    }

    function primaryKey(...$keys)
    {
        $value = '';

        foreach ($keys as $index => $key) {
            if ($index < count($keys) - 1) $value .= '`' . $key . '`, ';
            else $value .= '`' . $key . '`';
        }

        $this->primary = 'PRIMARY KEY (' . $value . ')';
    }

    function make()
    {
        $definition = [];

        foreach ($this->columns as $column) {
            $definition[] = $column->render();
        }

        if ($this->primary) $definition[] = $this->primary;

        if ($this->indexes) {
            foreach ($this->indexes as $index) {
                $definition[] = $index->render();
            }
        }

        $this->query->set('definition', $definition);
        
        return $this->query->exec()->status() === 'success';
    }
}
