<?php namespace spitfire\collection;

use BadMethodCallException;
use spitfire\collection\DefinedCollection;

/**
 * The collection class is intended to supercede the array and provide additional
 * functionality and ease of use to the programmer.
 */
class Collection extends DefinedCollection
{
	
	/**
	 * Flattens the collection. A collection may contain Collections and arrays of
	 * it's own. Flatten will return a single collection with all the items in it's
	 * first level.
	 * 
	 * This may destroy it's key relations.
	 * 
	 * @return Collection
	 */
	public function flatten() {
		$_ret  = new self();
		
		foreach ($this->toArray() as $item) {
			if ($item instanceof Collection) { $_ret->add($item->flatten()); }
			elseif (is_array($item))         { $c = new self($item); $_ret->add($c->flatten()); }
			else { $_ret->push($item); }
		}
		
		return $_ret;
	}
	
	/**
	 * This function checks whether a collection contains only elements with a 
	 * given type. This function also accepts base types.
	 * 
	 * Following base types are accepted:
	 * 
	 * <ul>
	 * <li>int</li><li>float</li>
	 * <li>number</li><li>string</li>
	 * <li>array</li>
	 * <ul>
	 * 
	 * @param string $type Base type or class name to check.
	 * @return boolean
	 */
	public function containsOnly($type) {
		switch($type) {
			case 'int'   : return $this->reduce(function ($p, $c) { return $p && is_int($c); }, true);
			case 'number': return $this->reduce(function ($p, $c) { return $p && is_numeric($c); }, true);
			case 'string': return $this->reduce(function ($p, $c) { return $p && is_string($c); }, true);
			case 'array' : return $this->reduce(function ($p, $c) { return $p && is_array($c); }, true);
			default      : return $this->reduce(function ($p, $c) use ($type) { return $p && is_a($c, $type); }, true);
		}
	}
	
	/**
	 * Removes all duplicates from the collection.
	 * 
	 * @return Collection
	 */
	public function unique() {
		return new Collection(array_unique($this->toArray()));
	}
	
	/**
	 * Adds up the elements in the collection. Please note that this method will
	 * double check to see if all the provided elements are actually numeric and
	 * can be added together.
	 * 
	 * @return int|float
	 * @throws BadMethodCallException
	 */
	public function sum() {
		if ($this->isEmpty())               { throw new BadMethodCallException('Collection is empty'); }
		if (!$this->containsOnly('number')) { throw new BadMethodCallException('Collection does contain non-numeric types'); }
		
		return array_sum($this->toArray());
	}
	
	/**
	 * 
	 * @param callable $callback A callback to invoke to sort the collection
	 * @return Collection
	 */
	public function sort($callback = null) : Collection {
		$copy = $this->toArray();
		
		if (!$callback) { sort($copy); }
		else            { usort($copy, $callback); }
		
		return new Collection($copy);
	}
	
	/**
	 * Returns the average value of the elements inside the collection.
	 * 
	 * @throws BadMethodCallException If the collection contains non-numeric values
	 * @return int|float
	 */
	public function avg() {
		return $this->sum() / $this->count();
	}
	
	/**
	 * 
	 * @param string $glue
	 * @return string
	 */
	public function join(string $glue) : string {
		return implode($glue, $this->toArray());
	}
	
	/**
	 * Extracts a certain key from every element in the collection. This requires
	 * every element in the collection to be either an object or an array.
	 * 
	 * The method does not accept values that are neither array nor object, but 
	 * will return null if the key is undefined in the array or object being used.
	 * 
	 * @param mixed $key
	 * @return Collection
	 */
	public function extract($key) : Collection {
		return new Collection(array_map(function ($e) use ($key) {
			if (is_array($e))  { return isset($e[$key])? $e[$key] : null; }
			if (is_object($e)) { return isset($e->$key)? $e->$key : null; }
			
			throw new OutOfBoundsException('Collection::extract requires array to contain only arrays and objects');
		}, $this->toArray()));
	}
	
	/**
	 * This method applies a given callback to all the elements inside the collection.
	 * It then returns a nested collection with the elements grouped by their return
	 * value.
	 * 
	 * Example:
	 * A collection containing strings ['a', 'b', 'c', 'dd', 'ee'] and a callback
	 * like strlen() will produce the following:
	 * 
	 * <code>$c->groupBy(function ($e) { return strlen($e); });</code>
	 * [ 1 => ['a', 'b', 'c'], 2 => ['dd', 'ee'] ]
	 * 
	 * @param \Closure|callable $callable
	 * @return Collection
	 */
	public function groupBy($callable) {
		$groups = new self();
		
		$this->each(function ($e) use ($groups, $callable) {
			$key = $callable($e);
			
			if (!isset($groups[$key])) {
				$groups[$key] = new self();
			}
			
			$groups[$key]->push($e);
		});
		
		return $groups;
	}
	
	/**
	 * Returns a collection that has the same elements, but in reverse order.
	 * 
	 * @return Collection
	 */
	public function reverse() : Collection {
		return new Collection(array_reverse($this->toArray()));
	}

}
