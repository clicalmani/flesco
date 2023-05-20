<?php
namespace Clicalmani\Flesco\Database;

class Delete extends DBQueryBuilder implements \IteratorAggregate {
	
	function __construct($params = array()) { 
		parent::__construct($params);
		
		$this->sql = 'DELETE ';

		if ( isset($this->params['signatures']) AND is_array($this->params['signatures']) ) {
			$this->sql .= join(',', $this->params['signatures']) . ' ';
		}

		if ( isset($this->params['tables']) AND is_array($this->params['tables']) ) {
			if ( count($this->params['tables']) > 1 OR isset($this->params['join']) ) {
				/**
				 * Multiple tables delete
				 */

				$prefix = $this->db->getPrefix();
				$tables = new \Clicalmani\Flesco\Collection\Collection;
				$tables->exchange($this->params['tables'])->map(function($val) use($prefix) {
					return $prefix . $val;
				})->toArray();
				
				/**
				 * Fields are tables from which the delete operation should occure
				 * it can contains tables names or tables alias.
				 */
				if ( isset($this->params['fields']) ) {
					$this->sql .= 'FROM ' . $this->params['fields'] . ' ';
				}

				$this->sql .= 'USING ' . join(',', (array) $tables) . ' '; 

				if ( isset($this->params['join']) AND is_array($this->params['join']) ) {
					foreach ($this->params['join'] as $joint ) {
						$this->sql .= static::JOIN_TYPES[strtolower($joint['type'])] . ' ' . $this->db->getPrefix() . $joint['table'] . ' ';

						if ( @ $joint['criteria'] ) {
							$this->sql .= $joint['criteria'] . ' ';
						}
					}
				}
			} else {
				/**
				 * Single table delete
				 */

				// Remove table alias
				$this->params['tables'][0] = $this->db->getPrefix() . preg_split('/\s/', $this->params['tables'][0], -1, PREG_SPLIT_NO_EMPTY)[0];

				$this->sql .= 'FROM ' . $this->params['tables'][0] . ' ';

				/**
				 * DELETE support explicit partition selection, which takes
				 * a list of comma-separated names of one or more partitions or subpartitions (or both)
				 * from which to select rows to be dropped. Partitions not included in the list are ignored.
				 * Given a partitioned table t with a partition named p0, executing the statement DELETE FROM t PARTITION (p0)
				 * has the same effect as executing ALTER TABLE t TRUNCATE PARTITION (p0); in both cases all rows of partition p0 
				 * are dropped.
				 */
				if ( isset($this->params['partitions']) AND is_array($this->params['partitions']) ) {
					$this->sql .= 'PARTITION (' . join(',', $this->params['partitions']) . ') ';
				}
			}

			if ( isset($this->params['where']) ) {
				$this->sql .= 'WHERE ' . $this->params['where'] . ' ';
			}

			if ( !(count($this->params['tables']) > 1 OR isset($this->params['join'])) ) {
				if ( isset($this->params['order_by']) ) {
					$this->sql .= 'ORDER BY ' . $this->params['order_by'] . ' ';
				}

				if ( isset($this->params['limit']) ) {
					$this->sql .= 'LIMIT ' . $this->params['limit'];
				}
			}
		}
	}
	
	function query() { 
		
	    $result = $this->db->query($this->bindVars($this->sql));
    	
		$this->status     = $result ? true: false;
	    $this->error_code = $this->db->errno();
	    $this->error_msg  = $this->db->error();
	}
	
	function getIterator() : \Traversable {
		return new DBQueryIterator($this);
	}
	
	function error() { parent::error(); }
}
?>