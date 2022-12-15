<?php
namespace Clicalmani\Flesco\Models;

use Clicalmani\Flesco\Database\DB;
use Clicalmani\Flesco\Database\DBQuery;
use Clicalmani\Flesco\Exception\ClassNotFoundException;
use Clicalmani\Flesco\Exception\MethodNotFoundException;

class Model 
{

    protected $table;
    protected $primaryKey;

    private $query;
    private $id;
    private $changes     = [];
    private $new_records = [];

    public function __construct($id = null)
    {
        $this->id = $id;
        $this->query = new DBQuery;
        $this->query->set('tables', [$this->table]); 
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
            $this->query->delete()->where($this->primaryKey . ' = ' . $this->id)->exec();
        } else throw new \Exception("Can not update or delete record when on safe mode");
    }

    public function safeDelete()
    {
        if (isset($this->id) AND isset($this->primaryKey)) {
            $this->query->delete()->where($this->primaryKey . ' = ' . $this->id)->exec();
        } else {

            $criteria = $this->query->getParam('where');

            if ( isset($criteria) ) {
                $this->query->delete()->where($criteria)->exec();
            } else throw new \Exception("Can not bulk update or delete records when on safe mode");
        }
    }

    public function update($values = [])
    {
        if (empty($values)) return false;

        if (isset($this->id) AND isset($this->primaryKey)) {
            $this->query->update($values)->where($this->primaryKey . ' = ' . $this->id)->exec();
        } else {

            $criteria = $this->query->getParam('where');

            if ( isset($criteria) ) {
                return $this->query->update($values)->where($criteria)->exec();
            } else throw new \Exception("Can not bulk update or delete records when on safe mode");
        }
    }

    public function insert($fields = [])
    {
        if (empty($fields)) return false;

        $this->query = DB_QUERY_INSERT;
        $this->query->unset('tables');
        $this->query->set('table', $this->table);

        if (count($fields) == count($fields, COUNT_RECURSIVE)) {
            $this->query->set('values', $fields);
        } else throw new \Exception("Column count doesn't match value count");

        return $this->query->exec();
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

    public function save()
    {
        $this->update( $this->changes );
        $this->insert( $this->new_records );
    }

    function __get($attribute)
    {
        if (isset($this->id) AND isset($this->primaryKey)) {

            $collection = $this->where($this->primaryKey . '=' . $this->id)->get($attribute);
            
            if ($collection->count()) {
                return $collection->first()[$attribute];
            }

            return null;
        } throw new \Exception("Access to undeclared property on object");
    }

    function __set($attribute, $value)
    {
        $this->query = DB_QUERY_INSERT;

        if (isset($this->id) AND isset($this->primaryKey)) {

            $collection = $this->where($this->primaryKey . '=' . $this->id)->get($attribute);
            
            if ($collection->count()) {
                $this->changes[$attribute] = $value;
            } else {
                $this->new_records[] = [
                    $attribute => $value
                ];
            }

        } throw new \Exception("Can not update or insert new record on unknow");
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