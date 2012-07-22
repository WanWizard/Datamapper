<?php
/**
 * Datamapper ORM - Vendor package loader library
 *
 * @package     DataMapper ORM
 * @category    Core
 * @author      Harro "WanWizard" Verton
 * @author		Fuel Development Team
 * @license		MIT License
 * @link        http://datamapper.wanwizard.eu
 * @version     2.0.0
 */

namespace Datamapper\Core;

/**
 * Datamapper Query core extension class
 */
class Query
{
	/***************************************************************************
	 * static methods
	 ***************************************************************************/

	/**
	 * static find() implementation
	 */
	public static function static_find($class, $id = null)
	{
		// create a new class object
		$object = new $class;

		// nothing passed?
		if (is_null($id))
		{
			// just return a new object
			return $object;
		}

		// return all that match
		elseif ($id === 'all')
		{
			return $object->get();
		}

		// return first or last row that matches
		elseif ($id === 'first' or $id === 'last')
		{
			foreach(call_user_func($class.'::primary_key') as $pk)
			{
				$object->query()->order_by($pk, $id == 'first' ? 'ASC' : 'DESC');
			}

			return $object->get(1);
		}

		// return the requested row by passed id
		else
		{
			// make sure the id is passed as an array
			$id = (array) $id;

			foreach(call_user_func($class.'::primary_key') as $pk)
			{
				$object->query()->where($pk, '=', current($id));
				next($id);
			}

			return $object->get(1);
		}
	}

	/**
	 * static find_by() implementation
	 */
	public static function static_find_by($class)
	{
		// create a new class object
		$object = new $class;

		// process the fields
		foreach ( static::process_find_fields(func_get_args()) as $type => $params )
		{
			foreach ( $params as $value )
			{
				$object->query()->{$type}(key($value), '=', reset($value));
			}
		}

		// and return the result
		return $object->get(1);
	}

	/**
	 * static find_by() implementation
	 */
	public static function static_find_all_by($class)
	{
		// create a new class object
		$object = new $class;

		// process the fields
		foreach ( static::process_find_fields(func_get_args()) as $type => $params )
		{
			foreach ( $params as $value )
			{
				$object->query()->{$type}(key($value), '=', reset($value));
			}
		}

		// and return the results
		return $object->get();
	}

	/**
	 * static count_by() implementation
	 */
	public static function static_count_by($class)
	{
var_dump('count_by', $class, $args);die();

		die('@TODO static count_by called');
	}

	/**
	 * deal with compound find fields
	 */
	protected static function process_find_fields($args)
	{
		// get the field definition
		array_shift($args);
		$fields = array_shift($args);

		$where = $or_where = array();

		if (($and_parts = explode('_and_', $fields)))
		{
			foreach ($and_parts as $and_part)
			{
				$or_parts = explode('_or_', $and_part);

				if (count($or_parts) == 1)
				{
					$where[] = array($or_parts[0] => array_shift($args));
				}
				else
				{
					foreach($or_parts as $or_part)
					{
						$or_where[] = array($or_part => array_shift($args));
					}
				}
			}
		}

		$options = count($args) > 0 ? array_pop($args) : array();

		if ( ! array_key_exists('where', $options))
		{
			$options['where'] = $where;
		}
		else
		{
			$options['where'] = array_merge($where, $options['where']);
		}

		if ( ! array_key_exists('or_where', $options))
		{
			$options['or_where'] = $or_where;
		}
		else
		{
			$options['or_where'] = array_merge($or_where, $options['or_where']);
		}

		return $options;
	}

	/***************************************************************************
	 * dynamic methods
	 ***************************************************************************/

	/**
	 * get() implementation
	 */
	public static function get($object, $limit = null, $offset = null)
	{
		// set limit and offset if needed
		is_null($limit) or $object->query()->limit($limit);
		is_null($offset) or $object->query()->offset($offset);

		// run the query
		$result = $object->query()->execute();

		// reset the query object
		$object->query(true);

		// hydrate the results
		return $object->hydrate($result);
	}

	/**
	 * get_one() implementation
	 */
	public static function get_one($object)
	{
		return static::get($object, 1);
	}

	/**
	 * where() implementation
	 */
	public static function where($object)
	{
		die('where called');
	}

	/**
	 * limit() implementation
	 */
	public static function limit($object, $limit = null)
	{
		// set limit if needed
		is_null($limit) or $object->query()->limit($limit);

		// return the object for chaining
		return $object;

	}

	/**
	 * offset() implementation
	 */
	public static function offset($object, $offset = null)
	{
		// set offset if needed
		is_null($offset) or $object->query()->offset($offset);

		// return the object for chaining
		return $object;

	}
}
