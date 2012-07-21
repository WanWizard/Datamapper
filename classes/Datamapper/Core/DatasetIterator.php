<?php
/**
 * Datamapper ORM - Vendor package loader library
 *
 * @package     DataMapper ORM
 * @category    Core
 * @author      Harro "WanWizard" Verton
 * @license		MIT License
 * @link        http://datamapper.wanwizard.eu
 * @version     2.0.0
 */

namespace Datamapper\Core;


class DatasetIterator implements \Iterator, \Countable
{
	/**
	 * The parent DataMapper object that contains important info.
	 *
	 * @var \Datamapper\Core\Model
	 */
	protected $parent;

	/**
	 * Results array
	 *
	 * @var array
	 */
	protected $result;

	/**
	 * Number of results
	 *
	 * @var int
	 */
	protected $count;

	/**
	 * Current position
	 *
	 * @var int
	 */
	protected $pos;

	/**
	 * @param	DataMapper	$object	Should be cloned ahead of time
	 * @param	array	$query	current object result
	 */
	function __construct($object, $data)
	{
		// store the object as a main object
		$this->parent = $object;

		// Now get the information on the current query object
		$this->result = $data;
		$this->count = count($data);
		$this->pos = 0;
	}

	/**
	 * Gets the item at the current index $pos
	 * @return DataMapper
	 */
	function current()
	{
		return $this->get($this->pos);
	}

	function key()
	{
		return $this->pos;
	}

	/**
	 * Gets the item at index $index
	 * @param int $index
	 * @return DataMapper
	 */
	function get($index)
	{
		// dehydrate the result if needed
		if ( is_array($this->result[$index]) )
		{
			$class = get_class($this->parent);
			$this->result[$index] = new $class($this->result[$index]);
		}

		return $this->result[$index];
	}

	function next()
	{
		$this->pos++;
	}

	function rewind()
	{
		$this->pos = 0;
	}

	function valid()
	{
		return ($this->pos < $this->count);
	}

	/**
	 * Returns the number of results
	 * @return int
	 */
	function count()
	{
		return $this->count;
	}

	// Alias for count();
	function result_count()
	{
		return $this->count;
	}
}
