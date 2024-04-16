<?php
namespace Clicalmani\Flesco\Models;

use Clicalmani\Database\DB;
use Clicalmani\Database\DBQuery;
use Clicalmani\Flesco\Exceptions\ModelException;
use Clicalmani\Flesco\Support\Log;

/**
 * Class AbstractModel
 * 
 * @package Clicalmani\Flesco
 * @author @clicalmani
 */
abstract class AbstractModel implements Joinable, \JsonSerializable
{
    use MultipleKeys;

    /**
     * Primary key value
     * 
     * @var string|array
     */
    protected $id;

    /**
     * DBQuery object
     * 
     * @var \Clicalmani\Database\DBQuery
     */
    protected $query;

    /**
     * Model table
     * 
     * @var string Table name
     */
    protected $table;

    /**
     * Table attributes.
     * 
     * @var array
     */
    protected $attributes = [];
    
    /**
     * Table primary key
     * 
     * @var string|array Primary key
     */
    protected $primaryKey;

    /**
     * Hidden attributes.
     * 
     * @var string[]
     */
    protected $hidden = [];

    /**
     * Fillable attributes
     * 
     * @var string[]
     */
    protected $fillable = [];

    /**
     * Lock state
     * 
     * @var bool
     */
    protected $locked = false;

    /**
     * Enable model events trigger
     * 
     * @var bool Enabled by default
     */
    public static $triggerEvents = true;

    /**
     * Append custom attributes
     * 
     * @var string[] Custom attributes
     */
    protected $custom = [];

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
     * Enable pagination
     * 
     * @var bool Default to false
     */
    protected $calc_found_rows = false;

    /**
     * Model data
     * 
     * @var \Clicalmani\Flesco\Models\Attribute[]
     */
    protected $data = [];

    /**
     * Event handlers
     * 
     * @var array<string, callable>
     */
    protected $eventHandlers = [];

    /**
     * Register model events
     * 
     * @return void
     */
    abstract protected function boot() : void;

    /**
     * Trigger event
     * 
     * @param string $event Event name
     * @param mixed $data Event data
     * @return void
     */
    abstract protected function triggerEvent(string $event, mixed $data = null) : void;

    /**
     * Constructor
     * 
     * @param array|string|null $id
     */
    public function __construct(array|string|null $id = null)
    {
        $this->id    = $id;
        $this->query = new DBQuery;

        $this->query->set('tables', [$this->table]);
    }

    /**
     * Returns table primary key value
     * 
     * @param bool $keep_alias When true table alias will be prepended to the key.
     * @return string|array
     */
    protected function getKey(bool $keep_alias = false) : string|array
    {
        if (false == $keep_alias) return $this->clean( $this->primaryKey );

        return $this->primaryKey;
    }

    /**
     * Return the model table name
     * 
     * @param bool $keep_alias Wether to include table alias or not
     * @return string Table name
     */
    protected function getTable(bool $keep_alias = false) : string
    {
        if ($keep_alias) return $this->table;
       
        @[$table, $alias] = explode(' ', $this->table);

        return $alias ? $table: $this->table;
    }

    /**
     * Enable lock state
     * 
     * @return void
     */
    protected function lock() : void
    {
        $this->locked = DB::table($this->table)->lock();
    }

    /**
     * Disable lock state
     * 
     * @return void
     */
    protected function unlock() : void
    {
        $this->locked = !DB::table($this->table)->unlock();
    }

    /**
     * Verify lock state
     * 
     * @return bool
     */
    protected function isLocked() : bool
    {
        return $this->locked;
    }

    /**
     * Verify if model is defined
     * 
     * @return bool
     */
    protected function isEmpty() : bool
    {
        return !($this->id && $this->primaryKey);
    }

    /**
     * Get model manupulated data
     * 
     * @return array
     */
    protected function getData() : array
    {
        $in = [];
        $out = [];

        foreach ($this->data as $attribute) {
            
            // Escape none fillable attributes for update
            if ( FALSE === $attribute->isFillable() && $attribute->access === Attribute::UPDATE) continue;

            // Nullify entry value if not defined
            $value = !$attribute->isNull() ? $attribute->value: null;

            if ($attribute->access === Attribute::INSERT) $in[$attribute->name] = $value;
            elseif ($attribute->access === Attribute::UPDATE) $out[$attribute->name] = $value;
        }

        if ( $in ) return ['in' => $in];
        if ( $out ) return ['out' => $out];

        return [];
    }

    /**
     * Query getter
     * 
     * @return \Clicalmani\Database\DBQuery
     */
    public function getQuery()
    {
        return $this->query;
    }

    /**
     * Fillable getter
     * 
     * @return string[]
     */
    public function getFillableAttributes() : array
    {
        return $this->fillable;
    }

    /**
     * Hidden getter
     * 
     * @return string[]
     */
    public function getHiddenAttributes() : array
    {
        return $this->hidden;
    }

    /**
     * Custom getter
     * 
     * @return string[]
     */
    public function getCustomAttributes() : array
    {
        return $this->custom;
    }

    public function join(Model|string $model, string|null $foreign_key = null, string|null $original_key = null, string $type = 'LEFT') : static 
    {
        $original_key = $original_key ?? $foreign_key;                              // The original key is the parent
                                                                                    // primary key
        
        /**
         * USING operator will be used to join the tables in case foreign key match original key
         * make sure that there is no alias in the key
         */
        if ($original_key == $foreign_key) {
            $original_key = $this->clean($original_key);
            $foreign_key  = $this->clean($foreign_key);
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

        if ($type === 'Cross') $this->query->{'join' . $type}($model->getTable(true));
        else $this->query->{'join' . $type}($model->getTable(true), $foreign_key, $original_key);

        return $this;
    }

    public function leftJoin(Model|string $model, ?string $foreign_key = null, ?string $original_key = null): static
    {
        return $this->join($model, $foreign_key, $original_key);
    }

    public function rightJoin(Model|string $model, ?string $foreign_key = null, ?string $original_key = null): static
    {
        return $this->join($model, $foreign_key, $original_key, 'RIGHT');
    }

    public function innerJoin(Model|string $model, ?string $foreign_key = null, ?string $original_key = null): static
    {
        return $this->join($model, $foreign_key, $original_key, 'INNER');
    }

    public function crossJoin(Model|string $model): static
    {
        return $this->join($model, null, null, 'CROSS');
    }

    public function jsonSerialize() : mixed
    {
        if (!$this->id) return null;

        $row = DB::table($this->getTable())->where($this->getKeySQLCondition())->get()->first();
        
        if ( !$row ) return null;

        // Attributes
        $data = [];
        foreach ($row as $name => $value) {
            $attribute = new Attribute($name, $value);
            $attribute->model = $this;
            $attribute->access = Attribute::READ;

            if ($attribute->isHidden()) continue;

            $data[$attribute->name] = $attribute->isNull() ? null: $attribute->value;
            $this->attributes[] = $attribute->name;
        }
        
        // Custom attributes
        $data2 = [];

        foreach ($this->custom as $name) {
            $attribute = new Attribute($name);
            $attribute->model = $this;
            $attribute->access = Attribute::READ;

            $data2[$name] = $attribute->getCustomValue();
        }
        
        return array_merge($data, $data2);
    }

    /**
     * @param string $name 
     * @return mixed
     */
    public function __get(string $name) : mixed
    {
        if ( empty($name) || $this->isEmpty() ) return null;

        $attribute = new Attribute($name);
        $attribute->model = $this;
        $attribute->access = Attribute::READ;
        
        if ( $attribute->isCustom() ) {
            return $this->{$attribute->customize()}();
        }

        /**
         * Hold up joints because the request will be made on the main query
         */
        $joint = $this->query->getParam('join');
        $this->query->unset('join');

        $collection = $this->query->set('where', $this->getKeySQLCondition(true))->get("`$name`");
        
        /**
         * Restore joints
         */
        $this->query->set('join', $joint);
            
        if ($row = $collection->first()) {
            return $row[$name];
        }
        return null;
        // $error = sprintf("%s does not exists.", $this::class);
        // throw new ModelException($error, ModelException::ERROR_3070);
    }

    /**
     * @param string $attribute
     * @param mixed $value
     * @return void
     */
    public function __set(string $name, mixed $value) : void
    {
        $db = DB::getInstance();
        $table = $db->getPrefix() . $this->getTable();
        $statement = $db->query("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '" . env('DB_NAME', '') . "' AND TABLE_NAME = '$table'");
        $found = false;

        while($row = $db->fetch($statement, \PDO::FETCH_NUM)) {
            if ($row[0] == $name) {
                $found = true;
                break;
            }
        }

        if (false !== $found) {

            $attribute = new Attribute($name, $value);
            $attribute->model = $this;

            if ( $this->id && $this->primaryKey ) {
                
                $attribute->access = Attribute::UPDATE;

                /**
                 * Updating data
                 */
                $this->data[] = $attribute;
            } else {

                $attribute->access = Attribute::INSERT;

                /**
                 * Inserting data
                 */
                $this->data[] = $attribute;
            }
        } else {
            $error = sprintf("Error: can not update or insert new record on table %s", $this->getTable());
            throw new ModelException($error, ModelException::ERROR_3060);
        }
    }
}
