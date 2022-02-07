<?php namespace spitfire\collection;

/**
 *
 * @template T
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
	 * @param Collection<T>|array<mixed,T>|null $e
	 */
	public function __construct(string $type, $e = null)
	{
		$this->type = $type;
		parent::__construct($e);
	}
	
	/**
	 *
	 * @param Collection<T> $elements
	 * @return TypedCollection<T>
	 */
	public function add(Collection $elements) : TypedCollection
	{
		assert($elements->containsOnly($this->type));
		parent::add($elements);
		return $this;
	}
	
	/**
	 *
	 * @param T $element
	 * @return TypedCollection<T>
	 */
	public function push($element) : TypedCollection
	{
		assert($element instanceof $this->type);
		parent::push($element);
		return $this;
	}
}
