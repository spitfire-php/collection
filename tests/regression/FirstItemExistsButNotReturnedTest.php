<?php namespace tests\spitfire\collection\regression;

use PHPUnit\Framework\TestCase;
use spitfire\collection\Collection;

/**
 * This test addresses a regression found in Spitfire where the first
 * item of a collection is not returned if it happens to exist but has
 * a falsy key.
 * 
 * This would cause an array with just one key like ["hello"] to be treated
 * as not having a first item. Which is not true.
 */
class FirstItemExistsButNotReturnedTest extends TestCase
{
	
	public function test1()
	{
		$collection = Collection::fromArray(['hello world']);
		$this->assertEquals('hello world', $collection->first());
	}
	
	public function test2()
	{
		$collection = new Collection();
		$collection->push('hello world');
		$this->assertEquals('hello world', $collection->first());
	}
	
	public function test3()
	{
		$collection = new Collection();
		$collection->push('garbage');
		$collection->push('hello world');
		$collection->shift();
		$this->assertEquals('hello world', $collection->first());
	}
}
