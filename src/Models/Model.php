<?php
namespace Clicalmani\Flesco\Models;

use Clicalmani\Database\DB;
use Clicalmani\Database\DBQuery;
use Clicalmani\Database\Factory\Factory;
use Clicalmani\Flesco\Exceptions\ModelException;

class Model implements ModelInterface, \JsonSerializable
{
    use ModelTrait;

    /**
     * Table primary key(s)
     * 
     * @var string|array Key value(s)
     */
    private $id;

    /**
     * DBQuery object
     * 
     * @var \Clicalmani\Database\DBQuery
     */
    private $query;

    /**
     * Enable model events trigger
     * 
     * @var bool Enabled by default
     */
    public static $triggerEvents = true;

    /**
     * Model table
     * 
     * @var string Table name
     */
    protected $table;
    
    /**
     * Table primary key
     * 
     * @var string|array Primary key value
     */
    protected $primaryKey;

    /**
     * Table attributes not included in the list will not show up when rendering a json response.
     * 
     * @var array Attributes list to render
     */
    protected $attributes = [];
    
    /**
     * Append custom attributes when rendering json response
     * 
     * @var array Custom attributes
     */
    protected $appendAttributes = [];

    /**
     * Enable or disable table insert warning for duplicate keys.
     * 
     * @var bool Default to false
     */
    protected $insert_ignore = false;

    /**
     * Select distincts rows
     * 
     * @var bool Default to false
     */
    protected $select_distinct = false;

    /**
     * Enable SQL CALC_FOUND_ROWS in the select result
     * 
     * @var bool Default to false
     */
    protected $calc_found_rows = false;

    /**
     * |--------------------------------------------------------------------------
     * |                        Private Properties
     * |--------------------------------------------------------------------------
     * 
     * Private properties
     */
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

        /**
         * Trigger model events
         */
        $this->boot();
    }

    /**
     * Return the model table name
     * 
     * @param bool $required_alias Wether to include table alias or not
     * @return string Table name
     */
    public function getTable($required_alias = false)
    {
        if ($required_alias) return $this->table;
       
        $arr = explode(' ', $this->table);
        return count($arr) > 1 ? $arr[0]: $this->table;
    }

    /**
     * Returns table primary key value
     * 
     * @param bool $required_alias When true table alias will be prepended to the key. Default to false
     * @return string|array
     */
    public function getKey($required_alias = false)
    {
        if (false == $required_alias) return $this->cleanKey( $this->primaryKey );

        return $this->primaryKey;
    }

    /**
     * Returns table selected attributes to show up in a json response.
     * 
     * @return array Attributes list
     */
    protected function getAttributes()
    {
        return $this->attributes;
    }

    /**
     * Returns model's registered custom attributes
     * 
     * @return array Custom attributes
     */
    protected function getCustomAttributes()
    {
        return $this->appendAttributes;
    }
    
    /**
     * Verify if table has alias
     * 
     * @return bool True if defined, false otherwise
     */
    private function isAliasRequired()
    {
        /**
         * Insert query
         */
        if ( $this->query->getParam('table') ) return false;

        /**
         * If table has alias then alias is required for attributes
         */
        if ( $this->query->getParam('tables') ) 
            foreach ($this->query->getParam('tables') as $table ) {
                if ( count( explode(' ', $table) ) > 1) return true;
            }
        
        return false;
    }

    /**
     * Returns the DBQuery object attached to the model
     * 
     * @return \Clicalmani\Database\DBQuery
     */
    private function getQuery()
    {
        return $this->query;
    }

    /**
     * Internal wrap of the PHP builtin function get_called_class()
     * 
     * @see get_called_class() function
     * @return string Class name
     */
    private static function getClassName()
    {
        return get_called_class();
    }

    /**
     * Return the model instance. Usefull for static methods call.
     * 
     * @param string|array $id [optional] Primary key value
     */
    private static function getInstance($id = null)
    {
        $class = static::getClassName();
        return with ( new $class($id) );
    }

    /**
     * Gets the query result
     * 
     * @param string $fields SQL select statement of the column to show up in the result set.
     * @return \Clicalmani\Collection\Collection
     */
    public function get($fields = '*')
    {
        try {
            if ( !$this->query->getParam('where') AND $this->id) {
                $this->query->set('where', $this->getCriteria( $this->isAliasRequired() ));
            }
    
            $this->query->set('distinct', $this->select_distinct);
            $this->query->set('calc', $this->calc_found_rows);
            
            return $this->query->get($fields);
            
        } catch (\PDOException $e) {
            throw new \Clicalmani\Database\Exceptions\DBQueryException($e->getMessage());
        }
    }

    /**
     * Insted of returning the raw result from the SQL database, every row in the result set will be
     * returned as a model instance.
     * 
     * @return \Clicalmani\Collection\Collection
     */
    public function fetch()
    {
        return $this->get()->map(function($row) {
            $instance = static::getInstance();
            return static::getInstance( $instance->guessKeyValue($row) );
        });
    }

    /**
     * Allows to define the where condition of the SQL statement
     * 
     * @param string $criteria [optional] 
     * @return \Clicalmani\Flesco\Models\Model Instance for chaining purpose.
     */
    public static function where($criteria = '1')
    {
        $instance = static::getInstance();
        $instance->getQuery()->where($criteria);

        return $instance;
    }

    /**
     * Alias of where. Useful when using where multiple times with AND as the conditional operator.
     * 
     * @param string $criteria [optional] 
     * @return \Clicalmani\Flesco\Models\Model Instance for chaining purpose.
     */
    public function whereAnd($criteria = '1')
    {
        $this->query->where($criteria);
        return $this;
    }

    /**
     * Same as whereAnd with the difference of operator which is in this case OR.
     * 
     * @param string $criteria [optional] 
     * @return \Clicalmani\Flesco\Models\Model Instance for chaining purpose.
     */
    public function whereOr($criteria = '1')
    {
        $this->query->where($criteria, 'OR');
        return $this;
    }

    /**
     * Define the SQL order by statement.
     * 
     * @param string $order SQL order by statement
     * @return \Clicalmani\Flesco\Models\Model Instance for chaining purpose.
     */
    public function orderBy($order)
    {
        $this->query->params['order_by'] = $order;
        return $this;
    }

    /**
     * Delete the model
     * 
     * @return bool true if success, false otherwise
     */
    public function delete()
    {
        if (isset($this->id) AND isset($this->primaryKey)) {

            /**
             * Don't add table alias for single delete.
             */
            $is_alias_required = count( $this->query->getParam('tables') ) > 1 ? true: false;
            $this->query->set('where', $this->getCriteria($is_alias_required));
            
        } else throw new \Exception("Can not update or delete records when on safe mode");

        // Before delete boot
        $this->callBootObserver('before_delete');
        
        $success = $this->query->delete()->exec()->status() == 'success';

        // After delete boot
        $this->callBootObserver('after_delete');

        return $success;
    }

    /**
     * Execute a registered model event observer
     * 
     * @param \Closure $observer Observer
     * @return void
     */
    private function callBootObserver($observer)
    {
        if (self::$triggerEvents && $this->{$observer}) {
            $closure = $this->{$observer};
            $closure(static::find($this->id));
        }
    }

    /**
     * Force delete the model when multiple rows must be affected.
     * 
     * @return bool True on success, false on failure
     */
    public function forceDelete()
    {
        // A delete operation must be set on a condition
        if (!empty($this->query->params['where'])) {
            return $this->query->delete()->exec()->status() == 'success';
        }

        throw new \Exception("Can not update or delete records when on safe mode");
    }

    /**
     * Update model
     * 
     * @param array $value [Optional] Attribuets values
     * @return bool True on success, false on failure
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

            if ($this->id AND $this->primaryKey) {
                // Before update boot
                $this->callBootObserver('before_update');
            }

            $fields = array_keys( $values );
		    $values = array_values( $values );

            $this->query->set('type', DBQuery::UPDATE);
            $this->query->set('fields',  $fields);
		    $this->query->set('values', $values);
            $this->query->set('where', $criteria);
            
            $success = $this->query->exec()->status() === 'success';

            $record = [];       // Updated attributes

            foreach ($fields as $index => $attr) {
                $record[$attr] = $values[$index];
            }
            
            /**
             * Check key change
             * 
             * Verify whether key(s) is/are among the updated attributes
             */
            $primary = is_string($this->primaryKey) ? [$this->primaryKey]: $this->primaryKey;
            $primary = $this->cleanKey($primary);  // Remove table alias

            collection()->exchange($primary)->map(function($pkey, $index) use($record) {
                if ( array_key_exists($pkey, $record) ) {               // The current key has been updated
                    if ( is_string($this->id) ) {
                        $this->id = $record[$pkey];                     // Update key value
                        return;
                    }

                    $this->id[$index] = $record[$pkey];                 // Update key value
                }
            });
            
            if ($this->id AND $this->primaryKey) {      
                // After update boot
                $this->callBootObserver('after_update');
            }
            
            return $success;
        } 
        
        throw new \Exception("Can not bulk update or delete records when on safe mode");
    }

    /**
     * Insert one or more rows in the table.
     * 
     * @param array $fields Row attributes values
     * @return bool True on success, false on failure
     */
    public function insert($fields = [])
    {
        if (empty($fields)) return false;

        $this->query->unset('tables');
        $this->query->set('type', DBQuery::INSERT);
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
        $this->callBootObserver('before_create');

        $success = $this->query->exec()->status() === 'success';

        $values = end($values);

        $record = [];

        foreach ($keys as $index => $key) {
            $record[$key] = $values[$index];
        }
        
        $this->id = $this->lastInsertId($record);
        
        // After create boot
        $this->callBootObserver('after_create');

        return $success;
    }

    /**
     * Alias of insert
     * 
     * @param array $fields Attributes values
     * @return bool True if success, false otherwise
     */
    public function create($fields = [])
    {
        return $this->insert($fields);
    }

    /**
     * Same as create with the difference of catching PDOException in case 
     * the query statement execution failed. Useful when one want to be informed wether
     * the new rows are inserted or not.
     * 
     * @param array $fields Attributes values
     * @return bool True if success, false otherwise
     */
    public function createOrFail($fields = [])
    {
        try {
            return $this->create($fields);
        } catch (\PDOException $e) {
            return false;
        }
    }

    /**
     * Define the from statement when deleting from joined models.
     * 
     * @param string $fields SQL FROM statement
     * @return \Clicalmani\Flesco\Models\Model Instance for chaining purpose.
     */
    public function from($fields)
    {
        $this->query->from($fields);
        return $this;
    }

    /**
     * In pratice a model can joined to another model. But in particular situation, one may be aiming to join
     * the model to a sub query (as this is possible with SQL). So this method allows such an operation.
     * 
     * @param string $query The sub query to joined to
     * @return \Clicalmani\Flesco\Models\Model Instance for chaining purpose.
     */
    public function subQuery($query)
    {
        $this->query->set('sub_query', $query);
        return $this;
    }

    /**
     * Returns distinct rows in the selection result.
     * 
     * @param bool $distinct True to enable or false to disable
     * @return \Clicalmani\Flesco\Models\Model Instance for chaining purpose.
     */
    public function distinct($distinct = true)
    {
        $this->select_distinct = $distinct;
        return $this;
    }

    /**
     * The current model inherit a foreign key
     * We should match the model key value to obtain its parent model.
     * 
     * @param string $class Parent model
     * @param string $foreign_key [Optional] Table foreign key
     * @param string $parent_key [Optional] original key
     * @return \Clicalmani\Flesco\Models\Model Instance for chaining purpose.
     */
    protected function belongsTo($class, $foreign_key = null, $original_key = null)
    { 
        if (!$this->id) {
            return null;
        }

        $child = new $class;

        $collection = $child->join($this, $foreign_key, $original_key)
                        ->whereAnd($this->getCriteria(true))
                        ->get();
        
        if (false == $collection->isEmpty()) {

            $row = $collection->first();
            $key = (new $class)->guessKeyValue($row);
            
            if (!is_array($key)) $key = $row[$this->cleanKey($foreign_key)];

            return $class::find($key);
        }
            
        return null;
    }

    /**
     * One an one relationship: the current model inherit a foreign key
     * 
     * @param string $class Parent model
     * @param string $foreign_key [Optional] Table foreign key
     * @param string $parent_key [Optional] original key
     * @return \Clicalmani\Flesco\Models\Model Instance for chaining purpose.
     */
    protected function hasOne($class, $foreign_key = null, $original_key = null)
    {
        // Make sure the model exists
        if (!$this->id) {
            return null;
        }

        $parent = new $class;
        
        $collection = $this->join($class, $foreign_key, $original_key)
                        ->whereAnd($this->getCriteria(true))
                        ->get();
           
        if (false == $collection->isEmpty()) {

            $row = $collection->first();
              
            return $class::find(
                (new $class)->guessKeyValue($row)
            ); 
        }
            
        return null;
    }

    /**
     * One to many relationship
     * 
     * @param string $class Child model
     * @param string $foreign_key [Optional] Table foreign key
     * @param string $original_key [Optional] Original key
     * @return \Clicalmani\Flesco\Models\Model Instance for chaining purpose.
     */
    protected function hasMany($class, $foreign_key = null, $original_key = null)
    {
        // Make sure the model exists
        if (!$this->id) {
            return null;
        }
        
        $child = new $class;
        
        return $this->join($class, $foreign_key, $original_key)
                    ->whereAnd($this->getCriteria(true))
                    ->get()
                    ->map(function($row) use($class, $foreign_key) {
                        $key = (new $class)->guessKeyValue($row);
                        return $class::find($key);
                    })->filter(function($instance)  use($class) {
                        return $instance instanceof $class;
                    });
    }

    /**
     * Save changes
     * 
     * @return bool True on success, false on failure
     */
    public function save()
    {
        $success = true;

        if (count($this->changes)) {
            $success = $this->update( $this->changes );
        }

        if (count($this->new_records)) {
            $success = $this->insert( [$this->new_records] );
        }

        // Reset back to select parameters 
        $this->changes     = [];
        $this->new_records = [];
        $this->query->set('type', DBQuery::SELECT);
        $this->query->set('tables', [$this->table]);
        unset($this->query->params['table']);

        return $success;
    }

    /**
     * Returns the last inserted ID for auto incremented keys
     * 
     * @param array $records A record to guess the ID from (Internal use)
     * @return string|array
     */
    public function lastInsertId($record = null)
    {
        $last_insert_id = DB::getPdo()->lastInsertId();

        if (!$last_insert_id AND $record) {
            $last_insert_id = $this->guessKeyValue($record);
        }

        return $last_insert_id;
    }

    /**
     * Returns the first value in the selected result
     * 
     * @return \Clicalmani\Flesco\Models\Model|null
     */
    public function first()
    {
        $collection = $this->get();
        
        if (false == $collection->isEmpty()) {
            $row = $collection->first();
            
            $primary_key = $this->guessKeyValue($row);

            return static::find($primary_key);
        }

        return null;
    }

    /**
     * Joins two models
     * 
     * @param string|\Clicalmani\Flesco\Models\Model $model Specified model
     * @param string $foreign_key [Optional] Foreign key
     * @param string $original_key [Optional] Original key
     * @param string $type [Optional] Join type default LEFT
     * @return \Clicalmani\Flesco\Models\Model Current model for chaining purpose.
     */
    public function join($model, $foreign_key = null, $original_key = null, $type = 'LEFT')
    {
        $original_key = $original_key ?? $foreign_key;                              // The original key is the parent
                                                                                    // primary key
        
        /**
         * USING operator will be used to join the tables in case foreign key match original key
         * make sure that there is no alias in the key
         */
        if ($original_key == $foreign_key) {
            $original_key = $this->cleanKey($original_key);
            $foreign_key  = $this->cleanKey($foreign_key);
        }

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

    /**
     * Defines SQL having statement
     * 
     * @param string $criteria Having statement
     * @return \Clicalmani\Flesco\Models\Model Instance for chaining purpose.
     */
    public function having($criteria)
    {
        $this->query->having($criteria);

        return $this;
    }

    /**
     * Defines SQL group by statement
     * 
     * @param string $criteria SQL group by statement
     * @return \Clicalmani\Flesco\Models\Model Instance for chaining purpose.
     */
    public function groupBy($criteria)
    {
        $this->query->groupBy($criteria);

        return $this;
    }

    /**
     * Ignores duplicates keys
     * 
     * @param bool $ignore
     * @return \Clicalmani\Flesco\Models\Model Instance for chaining purpose.
     */
    public function ignore(bool $ignore = true)
    {
        $this->insert_ignore = $ignore;
        return $this;
    }

    /**
     * Enable or disable SQL CALC_FOUND_ROWS
     * 
     * @param bool $calc
     * @return \Clicalmani\Flesco\Models\Model Instance for chaining purpose.
     */
    public function calcFoundRows(bool $calc = true)
    {
        $this->calc_found_rows = $calc;
        return $this;
    }

    /**
     * Limit the number of rows to be returned in the query result.
     * 
     * @param int $limit Number of rows
     * @return \Clicalmani\Flesco\Models\Model Instance for chaining purpose.
     */
    public function limit(int $limit = 0)
    {
        $this->query->set('limit', $limit);
        return $this;
    }

    /**
     * Sets the offset to start from when limit is set
     * 
     * @param int $offset
     * @return \Clicalmani\Flesco\Models\Model Instance for chaining purpose.
     */
    public function offset(int $offset = 0)
    {
        $this->query->set('offset', $offset);
        return $this;
    }

    /**
     * Returns a specified row defined by a specified primary key.
     * 
     * @param string|array $id Primary key value
     * @return \Clicalmani\Flesco\Models\Model|null
     */
    public static function find( $id ) 
    {
        $instance = static::getInstance($id);
        return $instance->get()->count() ? $instance: null;
    }

    /**
     * @deprecated
     * @see all method
     * @return \Clicalmani\Flesco\Models\Model|null
     */
    public static function findAll() 
    {
        $instance = static::getInstance();
        
        return $instance->get()->map(function($row) use($instance) {
            return static::getInstance( $instance->guessKeyValue($row) );
        });
    }

    /**
     * Returns all row from the query statement result
     * 
     * @return \Clicalmani\Collection\Collection
     */
    public static function all() 
    {
        return static::findAll();
    }

    /**
     * Filter the selected rows in a SQL result by using query parameters.
     * 
     * @param array $exclude Parameters to exclude
     * @param array $flag A flag can be used to order the result set by specifics request parameters or limit the 
     *  number of rows to be returned the result set.
     * @return \Clicalmani\Collection\Collection
     */
    public static function filter($exclude = [], $flag = [])
    {
        $flag = (object) $flag;

        $filters     = (new \Clicalmani\Flesco\Http\Requests\Request)->where($exclude);
        $child_class = static::getClassName();
        $child       = new $child_class;

        if ( $filters ) {
            try {
                $obj = $child_class::where(join(' AND ', $filters));

                if (@ $flag->order_by) {
                    $obj->orderBy($flag->order_by);
                }

                if (@ $flag->offset) {
                    $obj->offset($flag->offset);
                }

                if (@ $flag->limit) {
                    $obj->limit($flag->limit);
                }

                return $obj->fetch();

            } catch (\PDOException $e) {
                return collection();
            }
        }

        return $child_class::all();
    }

    /**
     * Insert new row or update row from request parameters
     * 
     * @return \Clicalmani\Flesco\Models\Model Instance for chaining purpose.
     */
    public function swap()
    {
        $db          = DB::getInstance();
        $table       = $db->getPrefix() . $this->getTable();
        $statement   = $db->query("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '" . env('DB_NAME', '') . "' AND TABLE_NAME = '$table'");
        
        while($row = $db->fetch($statement, \PDO::FETCH_NUM)) {
            foreach (array_keys(request()) as $attribute) {
                if ($row[0] == $attribute) {
                    $this->{$attribute} = request($attribute);
                    break;
                }
            }
        }
        
        return $this;
    }

    /**
     * @deprecated
     */
    public static function swapIn()
    {}

    /**
     * @deprecated
     */
    public function swapOut()
    {
        try {
            $this->swap();
            $this->save();
        } catch (\PDOException $e) {
            throw new ModelException($e->getMessage());
        }
    }

    /**
     * Override: Create a seed for the model
     * 
     * @return \Clicalmani\Database\Factory\Factory
     */
    public static function seed() : Factory
    {
        return Factory::new();
    }

    /**
     * Before create trigger
     * 
     * @param \Closure $closure Trigger function
     * @return void
     */
    protected function beforeCreate($closure) {
        if ($closure AND is_callable($closure, true, $before)) {
            $this->before_create = $closure;
        }
    }

    /**
     * After create trigger
     * 
     * @param \Closure $closure Trigger function
     * @return \Clicalmani\Flesco\Models\Model Instance for chaining purpose.
     */
    protected function afterCreate($closure) {
        if ($closure AND is_callable($closure, true, $after)) {
            $this->after_create = $closure;
        }
    }

    /**
     * Before update trigger
     * 
     * @param \Closure $closure Trigger function
     * @return \Clicalmani\Flesco\Models\Model Instance for chaining purpose.
     */
    protected function beforeUpdate($closure) {
        if ($closure AND is_callable($closure, true, $before)) {
            $this->before_update = $closure;
        }
    }

    /**
     * After update trigger
     * 
     * @param \Closure $closure Trigger function
     * @return \Clicalmani\Flesco\Models\Model Instance for chaining purpose.
     */
    protected function afterUpdate($closure) {
        if ($closure AND is_callable($closure, true, $after)) {
            $this->after_update = $closure;
        }
    }

    /**
     * Before delete trigger
     * 
     * @param \Closure $closure Trigger function
     * @return \Clicalmani\Flesco\Models\Model Instance for chaining purpose.
     */
    protected function beforeDelete($closure) {
        if ($closure AND is_callable($closure, true, $before)) {
            $this->before_delete = $closure;
        }
    }

    /**
     * After delete trigger
     * 
     * @param \Closure $closure Trigger function
     * @return \Clicalmani\Flesco\Models\Model Instance for chaining purpose.
     */
    protected function afterDelete($closure) {
        if ($closure AND is_callable($closure, true, $after)) {
            $this->after_delete = $closure;
        }
    }

    public function __get($attribute)
    {
        if (empty($attribute)) {
            return null;
        }

        // Make sure the model exists
        if (!$this->id) {
            return null;
        }

        if (in_array($attribute, $this->getCustomAttributes())) {
            $attribute = $this->sanitizeAttributeName($attribute);
            return $this->{$attribute}();
        }

        /**
         * Hold up joints because the request will be make on the main query
         */
        $joint = $this->query->getParam('join');
        $this->query->unset('join');

        $collection = $this->query->set('where', $this->getCriteria(true))->get("`$attribute`");

        /**
         * Restore joints
         */
        $this->query->set('join', $joint);
            
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
        return json_encode( $this );
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
                return isset($row[$value]) ? [$value => $row[$value]]: [$value => null];
            });
        
        $data = [];
        foreach ($collection as $row) {
            if ($row) $data[array_keys($row)[0]] = array_values($row)[0];
        }
        
        // Appended attributes
        $appended = $this->getCustomAttributes();

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
     * Register model events
     * 
     * @return void
     */
    public function boot()
    {
        /**
         * TODO
         */
    }
}
