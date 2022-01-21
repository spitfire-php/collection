<?php namespace spitfire\collection;

use ArrayAccess;
use BadMethodCallException;
use Exception;

/* 
 * The MIT License
 *
 * Copyright 2019 César de la Cal Bretschneider <cesar@magic3w.com>.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

/**
 * The defined collection provides a known amount of results, effectively making
 * it an array or Hashmap like structure.
 * 
 * In Spitfire we just wrap a few array functions inside this class to provide the
 * consistent behavior and extendability that we need.
 * 
 * @template T
 * @implements ArrayAccess<string|int, T>
 * @implements CollectionInterface<string|int,T>
 * 
 * @author César de la Cal Bretschneider <cesar@magic3w.com>
 */
class Collection implements ArrayAccess, CollectionInterface
{
	
	/**
	 * The array that this object wraps around. Most of the collection's methods
	 * are just convenience methods to make array operations more manageable.
	 *
	 * @var mixed[]
	 */
	private $items;
	
	/**
	 * The collection element allows to extend array functionality to provide
	 * programmers with simple methods to aggregate the data in the array.
	 * 
	 * @param Collection<T>|mixed $e
	 */
	public function __construct($e = null) {
		if ($e === null)                  {	$this->items = []; }
		elseif ($e instanceof Collection) { $this->items = $e->toArray(); }
		elseif (is_array($e))             { $this->items = $e; }
		else                              { $this->items = [$e]; }
	}
	
	/**
	 * Flattens the collection. A collection may contain Collections and arrays of
	 * it's own. Flatten will return a single collection with all the items in it's
	 * first level.
	 * 
	 * This may destroy it's key relations.
	 * 
	 * @return Collection<T>
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
	 * @return Collection<T>
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
	 * @return Collection<T>
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
	 * @return Collection<T>
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
	 * @return Collection<T>
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
	 * @return Collection<T>
	 */
	public function reverse() : Collection {
		return new Collection(array_reverse($this->toArray()));
	}
	
	/**
	 * This method iterates over the elements of the array and applies a provided
	 * callback to each of them. The value your function returns if placed in the
	 * array.
	 * 
	 * @param callable|string[] $callable
	 * @return Collection<T>
	 * @throws BadMethodCallException
	 */
	public function each($callable) : Collection {
		
		/*
		 * If the callback provided is not a valid callable then the function cannot
		 * properly continue.
		 */
		if (!is_callable($callable)) { 
			throw new BadMethodCallException('Invalid callable provided to collection::each()', 1703221329); 
		}
		
		return new Collection(array_map($callable, $this->items));
	}
	
	/**
	 * Reduces the array to a single value using a callback function.
	 * 
	 * @param callable $callback
	 * @param mixed    $initial
	 * @return mixed
	 */
	public function reduce($callback, $initial = null) {
		return array_reduce($this->items, $callback, $initial);
	}
	
	/**
	 * Reports whether the collection is empty.
	 * 
	 * @return boolean
	 */
	public function isEmpty() {
		return empty($this->items);
	}
	
	/**
	 * Returns true if the index passed is defined within the collection. Unlike
	 * contains, this checks whether the key is defined.
	 * 
	 * @param string|int $idx
	 * @return bool
	 */
	public function has($idx) {
		return array_key_exists($idx, $this->items);
	}
	
	/**
	 * Indicates whether an element is contained within this collection. 
	 * 
	 * @param mixed $e
	 * @return bool
	 */
	public function contains($e) {
		return array_search($e, $this->items, true) !== false;
	}
	
	/**
	 * Filters the collection using a callback. This allows a collection to shed
	 * values that are not useful to the programmer.
	 * 
	 * Please note that this will return a copy of the collection and the original
	 * collection will remain unmodified.
	 * 
	 * @param callable $callback
	 * @return Collection<T>
	 */
	public function filter($callback = null) {
		#If there was no callback defined, then we filter the array without params
		if ($callback === null) { return new Collection(array_filter($this->items)); }
		
		#Otherwise we use the callback parameter to filter the array
		return new Collection(array_filter($this->items, $callback));
	}
	
	/**
	 * Counts the number of elements inside the collection.
	 * 
	 * @return int
	 */
	public function count() : int {
		return count($this->items);
	}
	
	/**
	 * Adds an item to the list of items. This function then returns the element
	 * pushed. If you need method chaining, consider <code>$collection->add([$element])</code>
	 * 
	 * @param mixed $element
	 * @return mixed The element pushed
	 */
	public function push($element) {
		$this->items[] = $element;
		return $element;
	}
	
	/**
	 * Adds the elements from the array / collection provided to the current one.
	 * 
	 * @param Collection<T> $elements
	 * @return Collection<T>
	 */
	public function add(Collection $elements) {
		$this->items = array_merge($this->items, $elements->toArray());
		return $this;
	}
	
	/**
	 * Finds an item provided inside the collection and removes it from the collection.
	 * 
	 * @param mixed $element
	 * @return Collection<T>
	 * @throws OutOfBoundsException
	 */
	public function remove($element) {
		$i = array_search($element, $this->items, true);
		if ($i === false) { throw new OutOfBoundsException('Not found', 1804292224); }
		
		unset($this->items[$i]);
		return $this;
	}
	
	/**
	 * Empties the collection. The collection can afterwards be used normally. This 
	 * method can be overriden by other collection types that store metadata about
	 * this collection.
	 * 
	 * @return Collection<T>
	 */
	public function reset() {
		$this->items = [];
		return $this;
	}
	
	/**
	 * Returns the current element from the collection. This is used to provide
	 * the Iterator capabilities to the collection.
	 * 
	 * @return T
	 */
	public function current() {
		return current($this->items);
	}
	
	/**
	 * Returns the current key the collection is sitting at. Provides Iterator.
	 * 
	 * @return int|string|null
	 */
	public function key() {
		return key($this->items);
	}
	
	/**
	 * Advances the array pointer and returns the next element from the collection.
	 * Provides iterator.
	 * 
	 * @return T
	 */
	public function next() {
		return next($this->items);
	}
	
	/**
	 * Indicates whether the offset provided exists. Is virtually identical to has(),
	 * but a bit more verbose and required for ArrayAccess.
	 * 
	 * @param string|int $offset
	 * @return boolean
	 */
	public function offsetExists($offset) {
		return array_key_exists($offset, $this->items);
	}
	
	/**
	 * Returns the item from the collection with the given index. Unlike a regular
	 * array, a collection will throw an exception when requesting a index that 
	 * doesn't exist.
	 * 
	 * This ensures that the application doesn't enter an undefined state, but instead
	 * crashes early.
	 * 
	 * @param string|int $offset
	 * @return T
	 * @throws OutOfBoundsException
	 */
	public function offsetGet($offset) {
		if (!array_key_exists($offset, $this->items)) {
			throw new OutOfBoundsException('Undefined index: ' . $offset, 1703221322);
		}
		
		return $this->items[$offset];
	}
	
	/**
	 * Defines a certain index within the collection.
	 * 
	 * @param string|int $offset
	 * @param mixed $value
	 */
	public function offsetSet($offset, $value) {
		$this->items[$offset] = $value;
	}
	
	/**
	 * Removes the element with the given index from the collection.
	 * 
	 * @param int|string $offset
	 */
	public function offsetUnset($offset) {
		unset($this->items[$offset]);
	}
	
	/**
	 * Resets the internal array pointer of the collection. This method returns 
	 * the first element from the array.
	 * 
	 * @return T
	 */
	public function rewind() {
		return reset($this->items);
	}
	
	/**
	 * Returns the last item of the collection. This moves the pointer to the 
	 * last item.
	 * 
	 * @return T
	 * @throws OutOfBoundsException
	 */
	public function last() {
		if (empty($this->items)) { throw new OutOfBoundsException('Collection is empty', 1709042046); }
		return end($this->items);
	}
	
	/**
	 * Shifts the first element off the array. This removes the first element and 
	 * returns it.
	 * 
	 * @return T
	 */
	public function shift() {
		return array_shift($this->items);
	}
	
	/**
	 * Extracts a section of the collection, returning the extracted segment.
	 * 
	 * @param int $start
	 * @param int $size
	 * @return Collection<T>
	 * @throws Exception
	 */
	public function slice(int $start, int $size = 0) : Collection 
	{
		if ($size < 0) { throw new Exception('Invalid range'); }
		if ($size) { return new Collection(array_slice($this->items, $start, $size)); }
		else       { return new Collection(array_slice($this->items, $start)); }
	}
	
	/**
	 * Indicates whether the current element in the Iterator is valid. To achieve
	 * this we use the key() function in PHP which will return the key the array
	 * is currently forwarded to or (which is interesting to us) NULL in the event
	 * that the array has been forwarded past it's end.
	 * 
	 * @see key
	 * @return boolean
	 */
	public function valid() {
		return null !== key($this->items);
	}
	
	/**
	 * Returns the items contained by this Collection. This method may only work
	 * if the data the collection is managing is actually a defined set and not a
	 * pointer or something similar.
	 * 
	 * @return T[]
	 */
	public function toArray() {
		return $this->items;
	}
	
	/**
	 * This is functionally identical to has(), but provides compatibility with 
	 * the magic PHP method for isset()
	 * 
	 * @param string|int $name
	 * @return bool
	 */
	public function __isset($name) {
		return array_key_exists($name, $this->items);
	}
}
