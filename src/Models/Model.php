<?php
namespace Clicalmani\Flesco\Models;

use Clicalmani\Flesco\Database\DB;
use Clicalmani\Flesco\Database\DBQuery;
use Clicalmani\Flesco\Exceptions\ClassNotFoundException;
use Clicalmani\Flesco\Exceptions\MethodNotFoundException;
use Clicalmani\Flesco\Collection\Collection;

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
        
        if ( isset($this->id) ) {
            $this->query->where($this->primaryKey . '=' . $this->id);
        }
    }

    public function getTable()
    {
        return $this->table;
    }

    public function getKey()
    {
        $arr = explode('.', $this->primaryKey);
        return ( count($arr) > 1 ) ? $arr[1]: $this->primaryKey;
    }

    public function cleanKey($key)
    {
        $arr = explode('.', $key);
        return ( count($arr) > 1 ) ? $arr[1]: $key;
    }

    public function getQuery()
    {
        return $this->query;
    }

    public function get($fields = '*')
    {
        return $this->query->get($fields);
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

    /**
     * The current model inherit a foreign key
     * We should match the model key value to obtain its parent model.
     * 
     * @param [string] $class Parent model
     * @param [string] $foreign_key
     * @param [string] $parent_key original key
     * 
     * @return [Model] parent model
     */
    public function belongsTo($class, $foreign_key = null, $original_key = null)
    {
        $parent = new $class;

        $original_key = is_null($original_key) ? $parent->getKey(): $original_key;       // The original key is the parent
                                                                                         // primary key

        $foreign_key  = is_null($foreign_key) ? $original_key: $foreign_key;             // If $foreign_key is not set
                                                                                         // suppose that the foreign key is the
                                                                                         // original key
        $key = $this->cleanKey($foreign_key);
        
        $parent = new $class(
            /**
             * Obtain the foreign key value for the current relationship
             */
            $this->get($foreign_key)->first()[$key]
        );
        
        $parent->getQuery()->joinLeft($this->table, $foreign_key, $original_key);         // Join this model to its parent
        $parent->getQuery()->set('where', $this->query->getParam('where'));
        
        return $parent;
    }

    /**
     * One an one relationship: the current model inherit a foreign key
     * 
     * @param [string] $class Parent model
     * @param [string] $foreign_key
     * @param [string] $parent_key original key
     * 
     * @return [Model] current model
     */
    public function hasOne($class, $foreign_key = null, $orignal_key = null)
    {
        $child = new $class;

        $original_key = is_null($original_key) ? $this->primaryKey: $original_key;     // The original key is the parent
                                                                                       // primary key

        $foreign_key  = is_null($foreign_key) ? $original_key: $foreign_key;           // If $foreign_key is not set
                                                                                       // suppose that the foreign key is the
                                                                                       // original key
        
        // Avoid key alias
        $key = $this->cleanKey($child->getKey());
        
        return new $class( $child->where($foreign_key . '=' . $this->id)->get($child->getKey())->first()[$key] );
    }

    /**
     * One to many relationship: 
     * 
     * @param [string] $class Child model
     * @param [string] $foreign_key
     * @param [string] $original_key
     * @return [Model] parent model (current model)
     */
    public function hasMany($class, $foreign_key = null, $original_key = null)
    {
        $child = new $class;

        $original_key = is_null($original_key) ? $this->primaryKey: $original_key;     // The original key is the parent
                                                                                       // primary key

        $foreign_key  = is_null($foreign_key) ? $original_key: $foreign_key;           // If $foreign_key is not set
                                                                                       // suppose that the foreign key is the
                                                                                       // original key
        
        $key = $this->cleanKey($child->getKey());

        return $child->where($foreign_key . '=' . $this->id)->get($key)->map(function($arr) use($class, $key) {
            return new $class( $arr[$key] );
        });
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
        } throw new \Exception("Access to undeclared property $attribute on object");
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
        if ( ! method_exists($this, $method)) {
            throw new MethodNotFoundException($method);
        }

        $this->{$method}(...$args);

        return $this->query;
    }
}