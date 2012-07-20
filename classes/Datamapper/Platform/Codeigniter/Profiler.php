<?php
/**
 * Datamapper ORM - Vendor package loader library
 *
 * @package     DataMapper ORM
 * @category    Platforms
 * @author      Harro "WanWizard" Verton
 * @author		Fuel Development Team
 * @license		MIT License
 * @link        http://datamapper.wanwizard.eu
 * @version     2.0.0
 */

namespace Datamapper\Platform\Codeigniter;

/**
 * Datamapper Codeigniter Profiler interface class
 */
class Profiler extends \CI_DB
{
	public function __construct($connection, $database)
	{
		$this->connection = $connection;

		$this->database = '<span style="font-weight:bold;">Datamapper  &rArr; </span>'.$database;

		$this->queries = new ProfilerQueries($connection);

		$this->query_times = new ProfilerTimings($connection);
	}

}

/**
 * Datamapper Codeigniter Profiler Queries Simulator
 */
class ProfilerQueries implements \Iterator, \Countable
{
	public function __construct($connection)
	{
		$this->connection = $connection;
	}

	public function rewind()
	{
		reset($this->container);
	}

	public function current()
	{
		return current($this->container);
	}

	public function key()
	{
		return key($this->container);
	}

	public function next()
	{
		return next($this->container);
	}

	public function valid() {
		return $this->current() !== false;
	}

	public function count()
	{
		return count($this->container);
    }

	public function __get($var)
	{
		if ($var == 'container')
		{
			$this->container = array();
			foreach ( $this->connection->profilerQueries() as $key => $value)
			{
				$this->container[$key] = $value['query'];
			}
			return $this->container;
		}
	}

}

/**
 * Datamapper Codeigniter Profiler Query Timings Simulator
 */
class ProfilerTimings implements \ArrayAccess
{
	public function __construct($connection)
	{
		$this->connection = $connection;
	}

	public function offsetSet($offset, $value)
	{
		if (is_null($offset)) {
			$this->container[] = $value;
		} else {
			$this->container[$offset] = $value;
		}
	}

	public function offsetExists($offset)
	{
		return isset($this->container[$offset]);
	}

	public function offsetUnset($offset)
	{
		unset($this->container[$offset]);
	}

	public function offsetGet($offset)
	{
		return isset($this->container[$offset]) ? $this->container[$offset] : null;
	}

	public function __get($var)
	{
		if ($var == 'container')
		{
			$queries = array();
			foreach ( $this->connection->profilerQueries() as $key => $value)
			{
				$queries[$key] = $value['duration'];
			}
			return $queries;
		}
	}
}

