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
        /**
         * Single key table
         */
        if ( is_string($keys) ) {
            $arr = explode('.', $keys);
            return ( count($arr) > 1 ) ? $arr[1]: $keys;
        }
        
        return (array) (new \Clicalmani\Flesco\Collection\Collection)
            ->exchange($keys)->map(function($key) {
                $key = explode('.', trim($key));
                return ( count($key) > 1 ) ? end($key): $key;
            })->toArray();
    }

    function getKeyValuesFromRow($row)
    {
        $key = $this->getKey();

        if ( is_array($key) ) {

            $ids = [];

            foreach ($key as $k) {
                $ids[] = $row[$k];
            }

            return $ids;
        }

        return $row[$key];
    }

    function getCriteria()
    {
        $keys = $this->getKey();
        $criteria = null;
            
        if ( is_string($keys) ) {
            $criteria = $keys . ' = "' . $this->id . '"';
        } elseif ( is_array($keys) AND is_array($this->id) AND (count($keys) == count($this->id)) ) {

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
        $collection = new \Clicalmani\Flesco\Collection\Collection( explode('_', $name) );
        return 'get' . join('', $collection->map(function($value) {
            return ucfirst($value);
        })->toArray()) . 'Attribute';
    }
}