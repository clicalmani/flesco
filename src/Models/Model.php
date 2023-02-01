<?php
namespace Clicalmani\Flesco\Models;

use Clicalmani\Flesco\Database\DB;
use Clicalmani\Flesco\Database\DBQuery;
use Clicalmani\Flesco\Exceptions\ClassNotFoundException;
use Clicalmani\Flesco\Exceptions\MethodNotFoundException;
use Clicalmani\Flesco\Collection\Collection;

class Model implements \JsonSerializable
{

    protected $id;
    protected $table;
    protected $primaryKey;
    protected $attributes = [];
    protected $appendAttributes = [];

    protected $query;
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

    protected function getTable()
    {
        return $this->table;
    }

    protected function getKey()
    {
        return $this->cleanKey( $this->primaryKey );
    }

    protected function getAttributes()
    {
        return $this->attributes;
    }

    protected function setAttributes($attributes)
    {
        $this->attributes = $attributes;
    }

    protected function getAppendAttributes()
    {
        return $this->appendAttributes;
    }

    protected function setAppendAttributes($attributes)
    {
        $this->appendAttributes = $attributes;
    }

    protected function setKeyValue($value)
    {
        $this->id = $value;
    }

    private function cleanKey($key)
    {
        $arr = explode('.', $key);
        return ( count($arr) > 1 ) ? $arr[1]: $key;
    }

    private function getQuery()
    {
        return $this->query;
    }

    public function get($fields = '*')
    {
        return $this->query->get($fields);
    }

    public static function where($criteria = '1')
    {
        $child_class = get_called_class();
        $child = new $child_class;
        $child->getQuery()->where($criteria);
        return $child;
    }

    // public function delete()
    // {
    //     if (isset($this->id) AND isset($this->primaryKey)) {
    //         $this->query->delete()->where($this->primaryKey . ' = ' . $this->id)->exec();
    //     } else throw new \Exception("Can not update or delete record when on safe mode");
    // }

    public function delete()
    {
        if (isset($this->id) AND isset($this->primaryKey)) {
            $this->query->params['where'] = $this->primaryKey . ' = "' . $this->id . '"';
        }

        $collection = $this->get();
            
        if ($collection->count() === 1) {
            $row = $collection->first();
            $this->id = $row[$this->cleanKey($this->primaryKey)];
            $this->boot();
        }
        
        // A delete operation must contain a key
        if (false == strstr($this->query->params['where'], $this->cleanKey($this->primaryKey))) {
            throw new \Exception("Can not update or delete records when on safe mode");
        }

        return $this->query->delete()->exec();
    }

    public function update($values = [])
    {
        if (empty($values)) return false;

        if (isset($this->id) AND isset($this->primaryKey)) {
            return $this->query->update($values)->where($this->primaryKey . ' = "' . $this->id . '"')->exec();
        } else {

            $criteria = $this->query->getParam('where');

            if ( isset($criteria) ) {
                return $this->query->update($values)->where($criteria)->exec();
            } 
            
            throw new \Exception("Can not bulk update or delete records when on safe mode");
        }
    }

    public function insert($fields = [])
    {
        if (empty($fields)) return false;

        $this->query->unset('tables');
        $this->query->set('type', DB_QUERY_INSERT);
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
        
        return new $class( $child->where($foreign_key . '="' . $this->id . '"')->get($child->getKey())->first()[$key] );
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

        return $child->where($foreign_key . '="' . $this->id . '"')->get($key)->map(function($arr) use($class, $key) {
            return new $class( $arr[$key] );
        });
    }

    public function save()
    {
        $obj = null;
        
        if (count($this->changes)) {
            $obj = $this->update( $this->changes );
        }

        if (count($this->new_records)) {
            $obj = $this->insert( [$this->new_records] );
        }

        if ($obj) {
            return $obj->status() == 'success';
        }

        return false;
    }

    public function sanitizeAttributeName($name)
    {
        $collection = new Collection( explode('_', $name) );
        return 'get' . join('', $collection->map(function($value) {
            return ucfirst($value);
        })->toArray()) . 'Attribute';
    }

    public static function find( $id ) 
    {
        $child_class = get_called_class();
        $child = new $child_class;
        $child->setKeyValue($id);
        return $child;
    }

    public static function findAll() 
    {
        $child_class = get_called_class();
        $child = new $child_class;
        return $child->get()->map(function($row) use($child_class, $child) {
            return new $child_class($row[$child->getKey()]);
        });
    }

    /**
     * Call for every state modification
     */
    protected function boot()
    {}

    function __get($attribute)
    {
        if (empty($attribute)) {
            return null;
        }

        if (in_array($attribute, $this->getAppendAttributes())) {
            $attribute = $this->sanitizeAttributeName($attribute);
            return $this->{$attribute}();
        }

        if (isset($this->id) AND isset($this->primaryKey)) {

            $collection = $this->where($this->primaryKey . '="' . $this->id . '"')->get($attribute);
            
            if ($collection->count()) {
                return $collection->first()[$attribute];
            }

            return null;
        } 
        
        throw new \Exception("Access to undeclared property $attribute on object");
    }

    function __set($attribute, $value)
    {
        if ($this->get($attribute)->count()) {
            if (isset($this->id) AND isset($this->primaryKey)) {
                $this->changes[$attribute] = $value;
            } else {
                $this->new_records[$attribute] = $value;
            }

            return;
        }
        
        throw new \Exception("Can not update or insert new record on unknow");
    }

    function __toString()
    {
        $child_class = get_called_class();
        $child = new $child_class;
        return $child_class;
    }

    function jsonSerialize()
    {
        $collection = DB::table($this->getTable())->where($this->getKey() . '="' . $this->id . '"')->get();
        
        if (0 === $collection->count()) {
            return null;
        }

        $row = $collection->first();

        $collection
            ->exchange($this->getAttributes() ? $this->getAttributes(): array_keys($row))
            ->map(function($value, $key) use($row) {
                return isset($row[$value]) ? [$value => $row[$value]]: null;
            });

        $data = [];
        foreach ($collection as $row) {
            if ($row) $data[array_keys($row)[0]] = array_values($row)[0];
        }

        // Appended attributes
        $appended = $this->getAppendAttributes();

        $data2 = [];
        foreach ($appended as $name) {
            $attribute = $this->sanitizeAttributeName($name);
            
            if ( method_exists($this, $attribute) ) {
                $data2[$name] = $this->{$attribute}();
            }
        }

        return $collection
            ->exchange(array_merge($data, $data2))
            ->toObject();
    }
}