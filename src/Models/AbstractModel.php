<?php
namespace Clicalmani\Flesco\Models;

use Clicalmani\Database\DB;
use Clicalmani\Database\DBQuery;

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
    protected $hiddenAttributes = [];

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
    protected $customAttributes = [];

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
     * @var \Clicalmani\Flesco\Models\ModelData[]
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
     * @param \Clicalmani\Flesco\Models\Model $listener Callback
     * @return void
     */
    abstract protected function triggerEvent(string $event, Model $listener) : void;

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
     * Verify wether a given attribute is a custom attribut
     * 
     * @param string $attribute
     * @return bool True if success, false otherwise.
     */
    protected function isCustomAttribute(string $attribute) : bool
    {
        return in_array($attribute, $this->customAttributes);
    }

    /**
     * Sanitize a custom attribute
     * 
     * @param string $attribute
     * @return string
     */
    protected function sanitizeAttribute(string $attribute) : string
    {
        return 'get' . collection( explode('_', $attribute) )
                    ->map(fn(string $value) => ucfirst($value))
                    ->join() 
                    . 'Attribute';
    }

    /**
     * Append a custom attribute to the current model
     * 
     * @param string $attribute
     * @return mixed
     */
    protected function append(string $attribute) : mixed
    {
        if ( method_exists($this, $attribute) ) return $this->{$attribute}();

        return null;
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

        foreach ($this->data as $entry) {
            $value = !$entry->isNull() ? $entry->value: null; // Nullify entry value if not defined
            if ($entry->writing_mode === ModelData::WRITING_MODE_INSERT) $in[$entry->attribute] = $value;
            elseif ($entry->writing_mode === ModelData::WRITING_MODE_UPDATE) $out[$entry->attribute] = $value;
        }

        if ( $in ) return ['in' => $in];
        if ( $out ) return ['out' => $out];

        return [];
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
        if (!$this->id) {
            return null;
        }

        $collection = DB::table($this->getTable())->where($this->getKeySQLCondition())->get();
        
        if (0 === $collection->count()) {
            return null;
        }

        $row = $collection->first();
        
        $collection
            ->exchange($this->attributes ? $this->attributes: array_keys($row))
            ->map(function($value, $key) use($row) {
                return isset($row[$value]) ? [$value => $row[$value]]: [$value => null];
            });
        
        $data = [];
        foreach ($collection as $row) {
            if ($row) $data[array_keys($row)[0]] = array_values($row)[0];
        }
        
        // Appended attributes
        $appended = $this->customAttributes;

        $data2 = [];
        foreach ($appended as $name) {
            $attribute = $this->sanitizeAttribute($name);
            
            if ( method_exists($this, $attribute) ) {
                $data2[$name] = $this->{$attribute}();
            }
        }

        return $collection
            ->exchange(array_merge($data, $data2))
            ->toObject();
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
}
