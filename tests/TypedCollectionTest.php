<?php namespace tests\spitfire\collection;

use AssertionError;
use BadMethodCallException;
use Exception;
use PHPUnit\Framework\TestCase;
use spitfire\collection\TypedCollection;

class TypedCollectionTest extends TestCase
{
	
	public function testAdd()
	{
		$collection = new TypedCollection(Exception::class);
		$collection->push(new Exception());
		$this->assertEquals(1, $collection->count());
	}
	
	public function testAddBad()
	{
		/**
		 * Obviously, without assertions this code can't work
		 */
		if (ini_get('zend.assertions') != 1) {
			$this->markTestSkipped('Assertions are disabled on this system');
		}
		
		$this->expectException(AssertionError::class);
		$collection = new TypedCollection(BadMethodCallException::class, []);
		$collection->push(new Exception());
	}
}