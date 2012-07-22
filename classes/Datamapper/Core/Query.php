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
				$object->query()->orderBy($pk, $id == 'first' ? 'ASC' : 'DESC');

				// disable default order_by
				$object->_set_query_state('has_order_by', true);
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
	public static function select()
	{
		// get the arguments
		$args = func_get_args();

		// get the object to work on
		$object = array_shift($args);

		// get the select type
		$type = array_shift($args);

		// process the arguments
		if ( ! empty($args) )
		{
			foreach ( $args as $arg )
			{
				// make sure the argument is an array
				is_array($arg) or $arg = array($arg);

				// loop through them
				foreach ( $arg as $column )
				{
					// deal with the supported types
					switch ($type)
					{
						case false:
							$object->query()->select($column);
							break;

						case 'max':
						case 'min':
						case 'avg':
						case 'sum':
						case 'count':
							$object->query()->select(array(\Cabinet\DBAL\DB::fn($type,$column), $column));
							break;

						default:
							var_dump('unsupported select_type: '.$type);
							die();
					}
				}
			}
		}

		return $object;
	}

	/**
	 * distinct() implementation
	 */
	public static function distinct($object, $distinct = true)
	{
		$object->query()->distinct($distinct);

		return $object;
	}

	/**
	 * get() implementation
	 */
	public static function get($object, $limit = null, $offset = null)
	{
		// set limit and offset if needed
		is_null($limit) or $object->query()->limit($limit);
		is_null($offset) or $object->query()->offset($offset);

		// do we need to inject a default order_by?
		if ( $object->_get_query_state('has_order_by') !== true )
		{
			$object->order_by($object->_get_query_state('default_order_by'));
		}

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
	 * get_one() implementation
	 */
	public static function get_where()
	{
		// get the arguments
		$args = func_get_args();

		// get the object to work on
		$object = array_shift($args);

		// we need 3 arguments, first must be an array
		if ( count($args) and is_array($args[0]) )
		{
			// add the where clause
			array_unshift($args[0], $object);
			call_user_func_array('static::where', $args[0]);

			// make sure we have the others
			isset($args[1]) or $args[1] = null;
			isset($args[2]) or $args[2] = null;

			// run the get and return the result
			return static::get($object, $args[1], $args[2]);
		}

		// invalid where clause, just run a normal get
		return static::get($object);
	}

	/**
	 * where() implementation
	 */
	public static function where()
	{
		// get the arguments
		$args = func_get_args();

		// get the where type
		$type = array_shift($args);

		// get the object to work on
		if ( is_object($type) )
		{
			$object = $type;
			$type = 'and';
		}
		else
		{
			$object = array_shift($args);
		}

		// check what as been passed
		switch ( count($args) )
		{
			// must be an array of where clauses
			case 1:
				foreach( $args as $arg )
				{
					if ( ! is_array($arg) )
					{
						$arg = explode(' ', $arg, 3);
						$arg[2] = trim($arg[2], '"\'');
					}
					array_unshift($arg, $object);
					array_unshift($arg, $type);
					call_user_func_array('static::where', $arg);
				}
				break;

			// key/value pair with possible custom comparison
			case 2:
				// move the value first
				$args[2] = $args[1];

				// check if a custom comparison is present
				$op = strrchr($args[0], ' ');
				if ( in_array(trim(strtoupper($op)), array('=', 'LIKE')) )
				{
					$args[1] = trim($op);
					$args[0] = substr($args[0], 0, -strlen($op));
				}
				else
				{
					$args[1] = '=';
				}

				// break intentionally omitted

			// preferred key/comparison/value combination
			case 3:
				switch ($type)
				{
					case 'and not':
						$object->query()->andNotWhere($args[0], $args[1], $args[2]);
						break;
					case 'or not':
						$object->query()->orNotWhere($args[0], $args[1], $args[2]);
						break;
					case 'and':
						$object->query()->andWhere($args[0], $args[1], $args[2]);
						break;
					case 'or':
						$object->query()->orWhere($args[0], $args[1], $args[2]);
						break;
				}
				break;
		}

		return $object;
	}

	/**
	 * where_in() implementation
	 */
	public static function where_in()
	{
		$args = func_get_args();

		$args[3] = $args[2];
		$args[2] = 'in';

		array_unshift($args, 'and');

		return call_user_func_array('static::where', $args);
	}

	/**
	 * where_not_in() implementation
	 */
	public static function where_not_in()
	{
		$args = func_get_args();

		$args[3] = $args[2];
		$args[2] = 'in';

		array_unshift($args, 'and not');

		return call_user_func_array('static::where', $args);
	}

	/**
	 * or_where() implementation
	 */
	public static function or_where()
	{
		$args = func_get_args();
		array_unshift($args, 'or');

		return call_user_func_array('static::where', $args);
	}

	/**
	 * or_where_in() implementation
	 */
	public static function or_where_in()
	{
		$args = func_get_args();

		$args[3] = $args[2];
		$args[2] = 'in';

		array_unshift($args, 'or');

		return call_user_func_array('static::where', $args);
	}

	/**
	 * or_where_not_in() implementation
	 */
	public static function or_where_not_in()
	{
		$args = func_get_args();

		$args[3] = $args[2];
		$args[2] = 'in';

		array_unshift($args, 'or not');

		return call_user_func_array('static::where', $args);
	}

	/**
	 * like() implementation
	 */
	public static function like($object, $field, $value, $option = 'both')
	{
		// get the arguments
		$args = func_get_args();

		// get the where type
		$type = array_shift($args);

		// get the object to work on
		if ( is_object($type) )
		{
			$object = $type;
			$type = 'and';
		}
		else
		{
			$object = array_shift($args);
		}

		// get the field and value
		$field = array_shift($args);
		$value = array_shift($args);

		// get the option value
		if ( empty($args) or ! in_array($args[0], array('before', 'after', 'both')) )
		{
			$option = 'both';
		}

		switch ($option)
		{
			case 'before':
				$value = '%'.$value;
				break;
			case 'after':
				$value .= '%';
				break;
			case 'both':
				$value = '%'.$value.'%';
				break;
		}

		return call_user_func_array('static::where', array($type, $object, $field, 'LIKE', $value));
	}

	/**
	 * or_like() implementation
	 */
	public static function or_like()
	{
		// get the arguments
		$args = func_get_args();

		// make this an or
		array_unshift($args, 'or');

		return call_user_func_array('static::like', $args);
	}

	/**
	 * not_like() implementation
	 */
	public static function not_like()
	{
		// get the arguments
		$args = func_get_args();

		// make this an and not
		array_unshift($args, 'and not');

		return call_user_func_array('static::like', $args);
	}

	/**
	 * or_not_like() implementation
	 */
	public static function or_not_like()
	{
		// get the arguments
		$args = func_get_args();

		// make this an and not
		array_unshift($args, 'or not');

		return call_user_func_array('static::like', $args);
	}

	/**
	 * ilike() implementation
	 */
	public static function ilike()
	{
		// get the arguments
		$args = func_get_args();

		// make this an or
		array_unshift($args, 'and');

		// prepare the arguments
		$args[2] = \Cabinet\DBAL\DB::fn('UPPER',$args[2]);
		$args[3] = strtoupper($args[3]);

		return call_user_func_array('static::like', $args);
	}

	/**
	 * or_ilike() implementation
	 */
	public static function or_ilike()
	{
		// get the arguments
		$args = func_get_args();

		// make this an or
		array_unshift($args, 'or');

		// prepare the arguments
		$args[2] = \Cabinet\DBAL\DB::fn('UPPER',$args[2]);
		$args[3] = strtoupper($args[3]);

		return call_user_func_array('static::like', $args);
	}

	/**
	 * not_ilike() implementation
	 */
	public static function not_ilike()
	{
		// get the arguments
		$args = func_get_args();

		// make this an and not
		array_unshift($args, 'and not');

		// prepare the arguments
		$args[2] = \Cabinet\DBAL\DB::fn('UPPER',$args[2]);
		$args[3] = strtoupper($args[3]);

		return call_user_func_array('static::like', $args);
	}

	/**
	 * or_not_like() implementation
	 */
	public static function or_not_ilike()
	{
		// get the arguments
		$args = func_get_args();

		// make this an and not
		array_unshift($args, 'or not');

		// prepare the arguments
		$args[2] = \Cabinet\DBAL\DB::fn('UPPER',$args[2]);
		$args[3] = strtoupper($args[3]);

		return call_user_func_array('static::like', $args);
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

	/**
	 * offset() implementation
	 */
	public static function order_by()
	{
		// get the arguments
		$args = func_get_args();

		// get the object to work on
		$object = array_shift($args);

		// any arguments left?
		if ( ! empty($args) )
		{
			// check what type of arguments are passed
			if ( is_array($args[0]) )
			{
				$object->query()->orderBy($args[0]);
			}

			// no array, so must be a key-value pair
			elseif ( count($args) == 2 )
			{
				$object->query()->orderBy($args[0], $args[1]);
			}
		}

		// disable default order_by
		$object->_set_query_state('has_order_by', true);

		// return the object for chaining
		return $object;
	}
}
