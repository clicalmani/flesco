<?php
namespace Clicalmani\Flesco\Models;

use Clicalmani\Collection\Collection;
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

    public function __construct(array|string|null $id = null)
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
    public function getTable(bool $required_alias = false) : string
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
    public function getKey(bool $required_alias = false) : string|array
    {
        if (false == $required_alias) return $this->cleanKey( $this->primaryKey );

        return $this->primaryKey;
    }

    /**
     * Returns table selected attributes to show up in a json response.
     * 
     * @return array Attributes list
     */
    protected function getAttributes() : array
    {
        return $this->attributes;
    }

    /**
     * Returns model's registered custom attributes
     * 
     * @return array Custom attributes
     */
    protected function getCustomAttributes() : array
    {
        return $this->appendAttributes;
    }
    
    /**
     * Verify if table has alias
     * 
     * @return bool True if defined, false otherwise
     */
    private function isAliasRequired() : bool
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
    private function getQuery() : DBQuery
    {
        return $this->query;
    }

    /**
     * Internal wrap of the PHP builtin function get_called_class()
     * 
     * @see get_called_class() function
     * @return string Class name
     */
    private static function getClassName() : string
    {
        return get_called_class();
    }

    /**
     * Return the model instance. Usefull for static methods call.
     * 
     * @param string|array $id [optional] Primary key value
     * @return static|null
     */
    private static function getInstance(string|array $id = null) : static|null
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
    public function get(string $fields = '*') : Collection
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
     * Instead of returning a raw SQL statement result, every row in the result set will be
     * matched to a table model.
     * 
     * @param string $class [Optional] Model class to be returned. If not specified the outer left table model of the joint will be used.
     * @return \Clicalmani\Collection\Collection
     */
    public function fetch(?string $class = null) : Collection
    {
        return $this->get()->map(function($row) use($class) {
            if ($class) return $class::getInstance( with( new $class )->guessKeyValue($row) );
            return static::getInstance( with( static::getInstance() )->guessKeyValue($row) );
        });
    }

    /**
     * Define the where condition of the SQL statement
     * 
     * @param string $criteria [optional] 
     * @return static
     */
    public static function where(string $criteria = '1') : static
    {
        $instance = static::getInstance();
        $instance->getQuery()->where($criteria);

        return $instance;
    }

    /**
     * Alias of where. Useful when using where multiple times with AND as the conditional operator.
     * 
     * @param string $criteria [optional] 
     * @return static
     */
    public function whereAnd(string $criteria = '1') : static
    {
        $this->query->where($criteria);
        return $this;
    }

    /**
     * Same as whereAnd with the difference of operator which is in this case OR.
     * 
     * @param string $criteria [optional] 
     * @return static
     */
    public function whereOr(string $criteria = '1') : static
    {
        $this->query->where($criteria, 'OR');
        return $this;
    }

    /**
     * Define the SQL order by statement.
     * 
     * @param string $order SQL order by statement
     * @return static
     */
    public function orderBy(string $order) : static
    {
        $this->query->params['order_by'] = $order;
        return $this;
    }

    /**
     * Delete the model
     * 
     * @return bool true if success, false otherwise
     */
    public function delete() : bool
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
     * @param string $observer Observer
     * @return void
     */
    private function callBootObserver(string $observer) : void
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
    public function forceDelete() : bool
    {
        // A delete operation must be set on a condition
        if (!empty($this->query->params['where'])) {
            return $this->query->delete()->exec()->status() == 'success';
        }

        throw new \Exception("Can not update or delete records when on safe mode");
    }

    /**
     * Make a delete possible but never delete
     * 
     * @return false
     */
    public function softDelete() : bool
    {
        return DB::getInstance()->beginTransaction(function() {
            $this->delete();
            return false;
        });
    }

    /**
     * Update model
     * 
     * @param array $value [Optional] Attribuets values
     * @return bool True on success, false on failure
     */
    public function update(array $values = []) : bool
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
            $this->query->set('ignore', $this->insert_ignore);
            
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

            // Restore state
            $this->query->set('type', DBQuery::SELECT);
            
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
    public function insert(array $fields = []) : bool
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

        $this->query->unset('table');
        $this->query->set('type', DBQuery::SELECT);
        $this->query->set('tables', [$this->getTable()]);

        return $success;
    }

    /**
     * Alias of insert
     * 
     * @param array $fields Attributes values
     * @return bool True if success, false otherwise
     */
    public function create(array $fields = []) : bool
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
    public function createOrFail(array $fields = []) : bool
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
     * @return static
     */
    public function from(string $fields)
    {
        $this->query->from($fields);
        return $this;
    }

    /**
     * In pratice a model can joined to another model. But in particular situation, one may be aiming to join
     * the model to a sub query (as this is possible with SQL). So this method allows such an operation.
     * 
     * @param string $query The sub query to joined to
     * @return static
     */
    public function subQuery(string $query) : static
    {
        $this->query->set('sub_query', $query);
        return $this;
    }

    /**
     * Returns distinct rows in the selection result.
     * 
     * @param bool $distinct True to enable or false to disable
     * @return static
     */
    public function distinct(bool $distinct = true) : static
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
     * @return mixed
     */
    protected function belongsTo(string $class, string|null $foreign_key = null, string|null $original_key = null) : mixed
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
     * @return mixed
     */
    protected function hasOne(string $class, string|null $foreign_key = null, string|null $original_key = null) : mixed
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
     * @return \Clicalmani\Collection\Collection
     */
    protected function hasMany($class, $foreign_key = null, $original_key = null) : Collection
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
    public function save() : bool
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
     * @return mixed
     */
    public function lastInsertId(array $record = []) : mixed
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
     * @return static|null
     */
    public function first() : static|null
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
     * Join models
     * 
     * @param string|\Clicalmani\Flesco\Models\Model $model Specified model
     * @param string $foreign_key [Optional] Foreign key
     * @param string $original_key [Optional] Original key
     * @param string $type [Optional] Join type default LEFT
     * @return static
     */
    public function join(Model|string $model, string|null $foreign_key = null, string|null $original_key = null, string $type = 'LEFT') : static
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

        /**
         * Duplicate joints
         * 
         * If table is already joint, the first joint will be maintained
         */
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
     * @return static
     */
    public function having(string $criteria) : static
    {
        $this->query->having($criteria);

        return $this;
    }

    /**
     * Defines SQL group by statement
     * 
     * @param string $criteria SQL group by statement
     * @return static
     */
    public function groupBy(string $criteria) : static
    {
        $this->query->groupBy($criteria);

        return $this;
    }

    /**
     * Ignores duplicates keys
     * 
     * @param bool $ignore
     * @return static
     */
    public function ignore(bool $ignore = true) : static
    {
        $this->insert_ignore = $ignore;
        return $this;
    }

    /**
     * Enable or disable SQL CALC_FOUND_ROWS
     * 
     * @param bool $calc
     * @return static
     */
    public function calcFoundRows(bool $calc = true) : static
    {
        $this->calc_found_rows = $calc;
        return $this;
    }

    /**
     * Limit the number of rows to be returned in the query result.
     * 
     * @param int $limit Number of rows
     * @return static
     */
    public function limit(int $limit = 0) : static
    {
        $this->query->set('limit', $limit);
        return $this;
    }

    /**
     * Sets the offset to start from when limit is set
     * 
     * @param int $offset
     * @return static
     */
    public function offset(int $offset = 0) : static
    {
        $this->query->set('offset', $offset);
        return $this;
    }

    /**
     * Returns a specified row defined by a specified primary key.
     * 
     * @param string|array $id Primary key value
     * @return static|null
     */
    public static function find( $id ) : static|null
    {
        $instance = static::getInstance($id);
        return $instance->get()->count() ? $instance: null;
    }

    /**
     * @deprecated
     * @see all method
     * @return \Clicalmani\Collection\Collection
     */
    public static function findAll() : Collection
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
    public static function all() : Collection
    {
        return static::findAll();
    }

    /**
     * Filter the current SQL statement result by using a provided request query parameters.
     * The query parameters will be automatically fetched. A filter can be used to exclude some
     * specific parameters. 
     * 
     * @param array $exclude Parameters to exclude
     * @param array $options Options can be used to order the result set by specifics request parameters or limit the 
     *  number of rows to be returned in the result set.
     * @return \Clicalmani\Collection\Collection
     */
    public static function filter(array $exclude = [], array $options = []) : Collection
    {
        $options = (object) $options;

        $filters     = with (new \Clicalmani\Flesco\Http\Requests\Request)->where($exclude);
        $child_class = static::getClassName();

        if ( $filters ) {
            try {
                $obj = $child_class::where(join(' AND ', $filters));

                if (@ $options->order_by) {
                    $obj->orderBy($options->order_by);
                }

                if (@ $options->offset) {
                    $obj->offset($options->offset);
                }

                if (@ $options->limit) {
                    $obj->limit($options->limit);
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
     * @return static
     */
    public function swap() : static
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
     * @return bool
     */
    public function swapOut() : bool
    {
        try {
            $this->swap();
            return $this->save();
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
     * @param callable $closure Trigger function
     * @return void
     */
    protected function beforeCreate(?callable $closure) : void
    {
        if ($closure AND is_callable($closure, true, $before)) {
            $this->before_create = $closure;
        }
    }

    /**
     * After create trigger
     * 
     * @param callable $closure Trigger function
     * @return static
     */
    protected function afterCreate(?callable $closure) : void
    {
        if ($closure AND is_callable($closure, true, $after)) {
            $this->after_create = $closure;
        }
    }

    /**
     * Before update trigger
     * 
     * @param callable $closure Trigger function
     * @return void
     */
    protected function beforeUpdate(?callable $closure) : void
    {
        if ($closure AND is_callable($closure, true, $before)) {
            $this->before_update = $closure;
        }
    }

    /**
     * After update trigger
     * 
     * @param callable $closure Trigger function
     * @return void
     */
    protected function afterUpdate(?callable $closure) : void
    {
        if ($closure AND is_callable($closure, true, $after)) {
            $this->after_update = $closure;
        }
    }

    /**
     * Before delete trigger
     * 
     * @param callable $closure Trigger function
     * @return void
     */
    protected function beforeDelete(?callable $closure) : void
    {
        if ($closure AND is_callable($closure, true, $before)) {
            $this->before_delete = $closure;
        }
    }

    /**
     * After delete trigger
     * 
     * @param callable $closure Trigger function
     * @return void
     */
    protected function afterDelete(?callable $closure) : void
    {
        if ($closure AND is_callable($closure, true, $after)) {
            $this->after_delete = $closure;
        }
    }

    /**
     * @param string $attribute 
     * @return mixed
     */
    public function __get(string $attribute) : mixed
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

    /**
     * @param string $attribute
     * @param mixed $value
     * @return void
     */
    public function __set(string $attribute, mixed $value) : void
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

    public function __toString() : string
    {
        return json_encode( $this );
    }

    public function jsonSerialize() : mixed
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
    public function boot() : void
    {
        /**
         * TODO
         */
    }
}
