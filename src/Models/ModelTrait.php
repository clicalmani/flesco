<?php
namespace Clicalmani\Flesco\Models;

Trait ModelTrait
{
    /**
     * Removes table alias from the key
     * 
     * @param mixed $keys string for single key and array form multiple keys
     * @return mixed cleaned key(s)
     */
    function cleanKey(mixed $keys) : mixed
    {
        if (!$keys) return false;

        /**
         * Single key table
         */
        if ( is_string($keys) ) {
            $arr = explode('.', $keys);
            return ( count($arr) > 1 ) ? $arr[1]: $keys;
        }
        
        return (new \Clicalmani\Collection\Collection)
            ->exchange($keys)->map(function($key) {
                $key = explode('.', trim($key));
                return ( count($key) > 1 ) ? end($key): $key[0];
            })->toArray();
    }

    function guessKeyValue($row)
    {
        $key = $this->cleanKey( $this->getKey() );
        
        if ( is_array($key) ) {

            $ids = [];

            foreach ($key as $k) {
                $ids[] = $row[$k];
            }

            return $ids;
        }
        
        return @ $row[$key];
    }

    function getCriteria($required_alias = false)
    {
        $keys     = $this->getKey($required_alias);
        $criteria = null;
        
        if ( is_string($keys) ) {

            /**
             * |--------------------------------------------
             * |          ***** Warning *****
             * |--------------------------------------------
             * 
             * A string key type could not accept an array value.
             * This would result to a simple warning. But to avoid a possible mislead we just throw an exception
             */
            if ( is_array($this->id) ) throw new \InvalidArgumentException("String expected for $keys; got array " . json_encode($this->id));
            
            $criteria = $keys . ' = "' . $this->id . '"';
        
        } elseif ( is_array($keys) ) {

            /**
             * |--------------------------------------------
             * |          ***** Warning *****
             * |--------------------------------------------
             * 
             * An array key type could not accept a string value.
             * This would result to a simple warning. But to avoid a possible mislead we just throw an exception
             */
            if ( ! is_array($this->id) ) throw new \InvalidArgumentException("Array expected for key " . json_encode($keys) . "; got string $this->id");

            if ( count($keys) !== count($this->id) ) throw new \InvalidArgumentException("Keys count should match values count for " . json_encode($keys) . "; got " . json_encode($this->id));
            
            $criterias = [];
            
            for ($i=0; $i<count($keys); $i++) {
                $criterias[] = $keys[$i] . '="' . $this->id[$i] . '"';
            }

            $criteria = join(' AND ', $criterias);
        }
        
        return $criteria;
    }

    function sanitizeAttributeName($name)
    {
        $collection = new \Clicalmani\Collection\Collection( explode('_', $name) );
        return 'get' . join('', $collection->map(function($value) {
            return ucfirst($value);
        })->toArray()) . 'Attribute';
    }
}
