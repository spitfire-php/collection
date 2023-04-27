<?php namespace spitfire\collection;

/**
 *
 * @template T of Object
 * @extends Collection<T>
 */
class TypedCollection extends Collection
{
	
	/**
	 *
	 * @var class-string<T>
	 */
	private $type;
	
	/**
	 *
	 * @param class-string<T> $type
	 * @param T|null $e
	 */
	public function __construct(string $type, $e = null)
	{
		$this->type = $type;
		parent::__construct($e);
	}
	
	/**
	 *
	 * @param Collection<T> $elements
	 * @return self<T>
	 */
	public function add(Collection $elements) : self
	{
		assert($elements->containsOnly($this->type));
		parent::add($elements);
		return $this;
	}
	
	/**
	 *
	 * @param T $element
	 * @return T
	 */
	public function push($element)
	{
		assert($element instanceof $this->type);
		parent::push($element);
		return $element;
	}
	
	/**
	 *
	 * @template E of Object
	 * @param class-string<E> $type
	 * @param Collection<E> $parent
	 * @return TypedCollection<E>
	 */
	public static function fromCollection(string $type, Collection $parent) : TypedCollection
	{
		return (new self($type))->add($parent);
	}
}
