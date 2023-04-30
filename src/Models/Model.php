<?php
namespace Clicalmani\Flesco\Models;

use Clicalmani\Flesco\Database\DB;
use Clicalmani\Flesco\Database\DBQuery;
use Clicalmani\Flesco\Exceptions\ClassNotFoundException;
use Clicalmani\Flesco\Exceptions\MethodNotFoundException;
use Clicalmani\Flesco\Collection\Collection;

class Model implements ModelInterface, \JsonSerializable
{
    use ModelTrait;

    private $id;

    protected $query,
              $table,
              $primaryKey,
              $attributes = [],
              $appendAttributes = [];

    /*
     | ------------------------------------------
     |              DB QUERY FLAGS
     | ------------------------------------------
     | 
     | Enable or disable some specific flags
     */
    protected $insert_ignore = false,
              $select_distinct = false,
              $calc_found_rows = false;

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
        $this->id    = $id;
        $this->query = new DBQuery;

        $this->query->set('tables', [$this->table]);
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

    private function getQuery()
    {
        return $this->query;
    }

    public function get($fields = '*')
    {
        if ( !$this->query->getParam('where') AND $this->id) {
            $this->query->set('where', $this->getCriteria());
        }

        $this->query->set('distinct', $this->select_distinct);
        $this->query->set('calc', $this->calc_found_rows);
        
        return $this->query->get($fields);
    }

    public static function where($criteria = '1')
    {
        $child_class = get_called_class();
        $child = new $child_class;
        $child->getQuery()->where($criteria);
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
                $this->query->where($this->getCriteria());
            } else throw new \Exception("Can not update or delete records when on safe mode");
        }

        /**
         * Keep a copy for the object passed to the closure can be modified at any time
         */
        // $clone = clone $this->query;
        
        // Before create boot
        if ($this->before_delete) {
            $closure = $this->before_delete;
            $closure(self::find($this->id));
        }

        // $this->query = $clone;
        
        $success = $this->query->delete()->exec()->status() == 'success';

        // Before create boot
        if ($this->after_delete) {
            $this->after_delete($this);
        }

        return $success;
    }

    public function forceDelete()
    {
        // A delete operation must be set on a condition
        if (!empty($this->query->params['where'])) {
            return $this->query->delete()->exec()->status() == 'success';
        }

        throw new \Exception("Can not update or delete records when on safe mode");
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

        if ($this->id AND $this->primaryKey) {
            $criteria = $this->getCriteria();
        } else {
            $criteria = $this->query->getParam('where');
        }

        if ( $criteria ) {

            // Before update boot
            if ($this->before_update) {
                $this->before_update($this);
            }

            $fields = array_keys( $values );
		    $values = array_values( $values );

            $this->query->set('type', DB_QUERY_UPDATE);
            $this->query->set('fields',  $fields);
		    $this->query->set('values', $values);

            $success = $this->query->exec()->status() === 'success';

            // After update boot
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
        $this->query->set('ignore', $this->insert_ignore);

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

        $success = $this->query->exec()->status() === 'success';

        // After create boot
        if ($this->after_create) {
            $this->after_create($this);
        }

        return $success;
    }

    public function from($fields)
    {
        $this->query->from($fields);
        return $this;
    }

    public function subQuery($query)
    {
        $this->query->set('sub_query', $query);
        return $this;
    }

    public function distinct($distinct = true)
    {
        $this->select_distinct = $distinct;
        return $this;
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
        $my_class = get_class($this);
        return $obj->join($this, $foreign_key, $original_key)
                    ->get()
                    ->map(function($row) use($my_class) {
                        return $my_class::find(
                            (new $my_class)->getKeyValuesFromRow($row)
                        );
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

        return $class::find(
            (new $class)->getKeyValuesFromRow($row)
        );
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
                        return $class::find(
                            (new $class)->getKeyValuesFromRow($row)
                        );
                    });
    }

    public function save()
    {
        $obj = null;
        
        if (count($this->changes)) {
            $obj = $this->update( $this->changes );
        }

        if (count($this->new_records)) {
            $success = $this->insert( [$this->new_records] );
            $this->id = DB::getPdo()->lastInsertId();

            if (!$this->id) {
                $this->id = $this->getKeyValuesFromRow($this->new_records);
            }
        }

        // Reset back to select parameters 
        $this->changes     = [];
        $this->new_records = [];
        $this->query->set('type', DB_QUERY_SELECT);
        $this->query->set('tables', [$this->table]);
        unset($this->query->params['table']);

        return isset($success) ? $success: false;
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

    public function ignore(bool $ignore = false)
    {
        $this->query->set('ignore', $ignore);
        return $this;
    }

    public function calcFoundRows(bool $calc = false)
    {
        $this->query->set('calc', $calc);
        return $this;
    }

    public function limit(int $limit = 0)
    {
        $this->query->set('limit', $limit);
        return $this;
    }

    public function offset(int $offset = 0)
    {
        $this->query->set('offset', $offset);
        return $this;
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
            return new $child_class(
                $child->getKeyValuesFromRow($row)
            );
        });
    }

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

        // Make sure the model exists
        if (!$this->id) {
            return null;
        }

        if (in_array($attribute, $this->getAppendAttributes())) {
            $attribute = $this->sanitizeAttributeName($attribute);
            return $this->{$attribute}();
        }

        $collection = $this->query->set('where', $this->getCriteria())->get("`$attribute`");
            
        if ($collection->count()) {
            return $collection->first()[$attribute];
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

        $collection = DB::table($this->getTable())->where($this->getCriteria())->get();
        
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

    /**
     * Call for every state modification
     */
    public function boot()
    {
        /**
         * TODO
         */
    }
}