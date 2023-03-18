<?php
namespace Clicalmani\Flesco\Models;

use Clicalmani\Flesco\Database\DB;
use Clicalmani\Flesco\Database\DBQuery;
use Clicalmani\Flesco\Exceptions\ClassNotFoundException;
use Clicalmani\Flesco\Exceptions\MethodNotFoundException;
use Clicalmani\Flesco\Collection\Collection;

class Model implements \JsonSerializable
{

    protected $id,
              $table,
              $primaryKey,
              $attributes = [],
              $appendAttributes = [],
              $query;

    private $changes = [],
            $new_records = [],
            $before_create = null,
            $after_create = null,
            $before_update = null,
            $after_update = null,
            $before_delete = null,
            $after_delete = null;

    public function __construct($id = null)
    {
        $this->id = $id;
        $this->query = new DBQuery;
        $this->query->set('tables', [$this->table]);
        
        if ( $this->id ) {
            $this->query->where($this->primaryKey . ' = "' . $this->id . '"');
        }

        $this->boot();
    }

    protected function getTable($add_alias = false)
    {
        if ($add_alias) return $this->table;

        $arr = explode(' ', $this->table);
        return count($arr) > 1 ? $arr[0]: $this->table;
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
        if ($this->id) {
            $this->query->set('where', $this->getKey() . ' = "' . $this->id . '"');
        }
        
        return $this->query->get($fields);
    }

    public static function where($criteria = '1')
    {
        $child_class = get_called_class();
        $child = new $child_class;
        $child->getQuery()->where($criteria);
        $child->boot();
        return $child;
    }

    public function whereAnd($criteria = '1')
    {
        $this->query->where($criteria);
        return $this;
    }

    public function whereOr($criteria = '1')
    {
        $this->query->where($criteria, 'OR');
        return $this;
    }

    public function orderBy($order)
    {
        $this->query->params['order_by'] = $order;
        return $this;
    }

    public function delete()
    {
        // A delete operation must contain a key
        if (empty($this->query->params['where'])) {

            if (isset($this->id) AND isset($this->primaryKey)) {
                $this->query->where($this->primaryKey . ' = "' . $this->id . '"');
            } else throw new \Exception("Can not update or delete records when on safe mode");
        }

        // Before create boot
        if ($this->before_delete) {
            $closure = $this->before_delete;
            $closure($this);
        }

        $success = $this->query->delete()->exec()->status() == 'success';

        // Before create boot
        if ($this->after_delete) {
            $this->after_delete($this);
        }

        return $success;
    }

    /**
     * Updates current model
     * 
     * @param $values [optional] array new values
     *  [key1 => value1, key2 => value2, ...]
     * 
     * @return Clicalmani\Flesco\Database\Update instance or throw error
     */
    public function update($values = [])
    {
        if (empty($values)) return false;

        if (isset($this->id) AND isset($this->primaryKey)) {
            $criteria = $this->primaryKey . ' = "' . $this->id . '"';
        } else {
            $criteria = $this->query->getParam('where');
        }

        if ( isset($criteria) ) {

            // Before create boot
            if ($this->before_update) {
                $this->before_update($this);
            }

            $success = $this->query->update($values)->where($criteria)->exec();

            // Before create boot
            if ($this->after_update) {
                $this->after_update($this);
            }

            return $success;
        } 
        
        throw new \Exception("Can not bulk update or delete records when on safe mode");
    }

    /**
     * Inserts new model
     * 
     * @param $fields [optional] array
     *  [
     *      [key1 => value1, key2 => value2, ...],
     *      [key1 => value1, key2 => value2, ...],
     *      [key1 => value1, key2 => value2, ...],
     *      ....
     *  ]
     * 
     * @return Clicalmani\Flesco\Database\Insert instance or throw error
     */
    public function insert($fields = [])
    {
        if (empty($fields)) return false;

        $this->query->unset('tables');
        $this->query->set('type', DB_QUERY_INSERT);
        $this->query->set('table', $this->getTable());

        $keys = [];
        $values = [];

        foreach ($fields as $field) {
            if (empty($keys)) {
                $keys = array_keys($field);
            } else {
                if (count($keys) !== count(array_keys($field))) {
                    throw new \Exception("Column count doesn't match value count");
                }
            }

            $values[] = array_values($field);
        }
        
        $this->query->set('fields', $keys);
        $this->query->set('values', $values);

        // Before create boot
        if ($this->before_create) {
            $this->before_create($this);
        }

        $success = $this->query->exec();

        // After create boot
        if ($this->after_create) {
            $this->after_create($this);
        }

        return $success;
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
    protected function belongsTo($class, $foreign_key = null, $original_key = null)
    { 
        // Make sure the model exists
        if (!$this->id) {
            return null;
        }

        $child = new $class;

        $obj = new $class;
        $key = $this->getKey();
        $my_class = get_class($this);
        return $obj->join($this, $foreign_key, $original_key)
                    ->get()
                    ->map(function($row) use($my_class, $key) {
                        return $my_class::find($row[$key]);
                    });
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
    protected function hasOne($class, $foreign_key = null, $original_key = null)
    {
        // Make sure the model exists
        if (!$this->id) {
            return null;
        }

        $parent = new $class;

        $row = $this->join($class, $foreign_key, $original_key)
                    ->get()
                    ->first(); 
        $obj = new $class;
        return $class::find($row[$obj->getKey()]);
    }

    /**
     * One to many relationship: 
     * 
     * @param [string] $class Child model
     * @param [string] $foreign_key
     * @param [string] $original_key
     * @return [Model] parent model (current model)
     */
    protected function hasMany($class, $foreign_key = null, $original_key = null)
    {
        // Make sure the model exists
        if (!$this->id) {
            return null;
        }

        $child = new $class;

        return $this->join($class, $foreign_key, $original_key)
                    ->get()
                    ->map(function($row) use($class) {
                        $obj = new $class;
                        return $class::find($row[$obj->getKey()]);
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
            $this->id = DB::getPdo()->lastInsertId();

            if (!$this->id AND array_key_exists($this->getKey(), $this->new_records)) {
                $this->id = $this->new_records[$this->getKey()];
            }
        }

        // Reset back to select parameters
        $this->changes     = [];
        $this->new_records = [];
        $this->query->set('type', DB_QUERY_SELECT);
        $this->query->set('tables', [$this->table]);
        unset($this->query->params['table']);

        if ($obj) {
            return $obj->status() == 'success';
        }

        return false;
    }

    /**
     * Joins the current model to the specified model
     * 
     * @param $model [string | Object] Specified model
     * 
     * @return DBQuery
     */
    public function join($model, $foreign_key = null, $original_key = null, $type = 'LEFT')
    {
        $original_key = is_null($original_key) ? $this->getKey(): $original_key;     // The original key is the parent
                                                                                       // primary key

        $foreign_key  = is_null($foreign_key) ? $original_key: $foreign_key;           // If $foreign_key is not set
                                                                                       // suppose that the foreign key is the
                                                                                       // original key
        
        if (is_string($model)) {
            $model = new $model;
        }

        $joints = $this->query->getParam('join');

        if ( $joints ) {
            foreach ($joints as $joint) {
                if ($joint['table'] == $model->getTable(true)) {                            // Table already joint
                    return $this;
                }
            }
        }

        $type = ucfirst(strtolower($type));

        $this->query->{'join' . $type}($model->getTable(true), $foreign_key, $original_key);

        return $this;
    }

    public function having($criteria)
    {
        $this->query->having($criteria);

        return $this;
    }

    public function groupBy($criteria)
    {
        $this->query->groupBy($criteria);

        return $this;
    }

    private function sanitizeAttributeName($name)
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

    protected function beforeCreate($closure) {
        if ($this->before_create AND is_callable($this->before_create, true, $before)) {
            $this->before_create = $before;
        }
    }

    protected function afterCreate($closure) {
        if ($this->after_create AND is_callable($this->after_create, true, $after)) {
            $this->after_create = $after;
        }
    }

    protected function beforeUpdate($closure) {
        if ($this->before_update AND is_callable($this->before_update, true, $before)) {
            $this->before_update = $before;
        }
    }

    protected function afterUpdate($closure) {
        if ($this->after_update AND is_callable($this->after_update, true, $after)) {
            $this->after_update = $after;
        }
    }

    protected function beforeDelete($closure) {
        if ($closure AND is_callable($closure, true, $before)) {
            $this->before_delete = $closure;
        }
    }

    protected function afterDelete($closure) {
        if ($this->after_delete AND is_callable($this->after_delete, true, $after)) {
            $this->after_delete = $after;
        }
    }

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

            $collection = $this->where($this->primaryKey . '="' . $this->id . '"')->get("`$attribute`");
            
            if ($collection->count()) {
                return $collection->first()[$attribute];
            }

            return null;
        } 
        
        throw new \Exception("Access to undeclared property $attribute on object");
    }

    function __set($attribute, $value)
    {
        $db = DB::getInstance();
        $table = $db->getPrefix() . $this->getTable();
        $statement = $db->query("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '" . env('DB_NAME', '') . "' AND TABLE_NAME = '$table'");
        $found = false;

        while($row = $db->fetch($statement, \PDO::FETCH_NUM)) {
            if ($row[0] == $attribute) {
                $found = true;
                break;
            }
        }

        if (false !== $found) {
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

    function jsonSerialize() : mixed
    {
        if (!$this->id) {
            return null;
        }

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