<?php
namespace Clicalmani\Flesco\App\Models;

use Clicalmani\Flesco\Database\DB;
use Clicalmani\Flesco\Database\DBQuery;
use Clicalmani\Flesco\App\Exception\ClassNotFoundException;
use Clicalmani\Flesco\App\Exception\MethodNotFoundException;

class Model {

    protected $table;
    protected $primaryKey;

    private $query;
    private $id;

    public function __construct($id = null)
    {
        $this->id = $id;
        $this->query = new DBQuery;
    }

    public function getTable()
    {
        return $this->table;
    }

    public function getKey()
    {
        return $this->parimaryKey;
    }

    public function getQuery()
    {
        return $this->query;
    }

    public function get($fields = '*')
    {
        $this->query->select($fields);
        return $this->query->get();
    }

    public function where($criteria = '1')
    {
        $this->query->where($criteria);
        return $this;
    }

    public function delete()
    {
        if (isset($this->id) AND isset($this->primaryKey)) {
            $this->query->where($this->primaryKey . ' = ' . $this->id);
        }

        $criteria = $this->query->getParam('where');

        if (isset($criteria)) {
            DB::table($this->table)->delete()->where($criteria);
        }
    }

    public function update($values = [])
    {
        return $this->query->update($values);
    }

    public function belongsTo($class)
    {
        $parent = new $class();

        $this->query->join( $parent->getTable() );

        return $this->query;
    }

    public function hasOne($class, $parent_id = null, $child_id = null)
    {
        $parent = new $class(
            $parent_id
        );

        $parent_id = ! isset($parent_id) ? $parent->getKey(): $parent_id;
        $child_id  = ! isset($child_id) ? $this->getKey(): $child_id;

        $this->query->joinLeft($parent->getTable(), $parent_id, $child_id);

        return $this->query;
    }

    public function hasMany($class, $parent_id = null, $child_id = null)
    {
        $parent = new $class(
            $parent_id
        );

        $parent_id = ! isset($parent_id) ? $parent->getKey(): $parent_id;
        $child_id  = ! isset($child_id) ? $this->getKey(): $child_id;

        $this->query->joinLeft($parent->getTable(), $parent_id, $child_id);

        return $this->query;
    }

    function __call($method, $args)
    {
        $class = 'App\\Models\\' . ucfirst($method);

        if ( ! class_exists($class) ) {
            throw new ClassNotFoundException($class);
        }

        if ( method_exists($this, $method)) {
            throw new MethodNotFoundException($method);
        }

        $this->{$method}();

        return $this->query;
    }
}