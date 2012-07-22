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

use \Datamapper\Platform\Driver as Platform;

/**
 * Datamapper Model base class
 */
class Model implements \ArrayAccess, \IteratorAggregate
{
	/***************************************************************************
	 * Class constants
	 ***************************************************************************/

	/**
	 * @var	string	current dataMapper version
	 */
	const VERSION = '2.0.0';

	/***************************************************************************
	 * Static usage
	 ***************************************************************************/

	/**
	 * @var  array  name or names of the primary keys
	 */
	protected static $_primary_key = array('id');

	/**
	 * @var  array  model configuration
	 */
	// protected static $_config;

	/**
	 * @var  string  table name to overwrite assumption
	 */
	// protected static $_table_name;

	/**
	 * @var  array  array of object properties
	 */
	// protected static $_properties;

	/**
	 * @var  array  array of observer classes to use
	 */
	// protected static $_observers;

	/**
	 * @var  array  relationship properties
	 */
	// protected static $_has_one;
	// protected static $_belongs_to;
	// protected static $_has_many;
	// protected static $_many_many;

	/**
	 * @var	string	cached model connections
	 */
	protected static $_connection_cached = array();

	/**
	 * @var	array	cached model configurations
	 */
	protected static $_config_cached = array();

	/**
	 * @var	array	cached model table names
	 */
	protected static $_table_names_cached = array();

	/**
	 * @var	array	cached model properties
	 */
	protected static $_properties_cached = array();

	/**
	 * @var	string	cached model relationships
	 */
	protected static $_relations_cached = array();

	/**
	 * @var	array	cached model observers
	 */
	protected static $_observers_cached = array();

	/**
	 * @var	array	cached extension methods
	 */
	protected static $_methods_cached = array();

	/**
	 * @var	array	cached static extension methods
	 */
	protected static $_static_methods_cached = array();

	/**
	 * @var	array	array of valid relation types
	 */
	protected static $_valid_relations = array(
		'belongs_to'    => 'Datamapper\\Relation\\BelongsTo',
		'has_one'       => 'Datamapper\\Relation\\HasOne',
		'has_many'      => 'Datamapper\\Relation\\HasMany',
		'many_many'     => 'Datamapper\\Relation\\ManyMany',
	);

	/**
	 * Static calls magic method, allows for dynamic static method extensions
	 *
	 * @param	string	config key to retrieve
	 * @param	mixed	default value in case the key doesn't exist
	 *
	 * @return  mixed
	 */
	public static function __callStatic($method, $args)
	{
		$class = get_called_class();

		// store the method name, we need it later
		$index = $method;

		// do we have a method by this name?
		if ( empty(static::$_static_methods_cached[$class][$index]) )
		{
			// check it it's a wildcard method
			foreach ( array_keys(static::$_static_methods_cached[$class]) as $index )
			{
				if ( substr($index,-1) === '*')
				{
					if ( strpos($method, substr($index,0,-1)) === 0 )
					{
						// add the remainder of the method name to the argument stack
						array_unshift($args, substr($method, strlen($index)));

						// and set the requested method
						$method = substr($index,0,-1);

						break;
					}
				}
			}

			// if we didn't find the wildcard, bail out
			if ( $index == $method )
			{
				throw new \Datamapper\Exceptions\DatamapperException('unknown static method "'.$method.'" called');
			}
		}

		// push the current classname on the arguments stack
		array_unshift($args, $class);

		// call the extension method and return the result
		return call_user_func_array(static::$_static_methods_cached[$class][$index].'::static_'.$method, $args);
	}

	/**
	 * Fetch the database connection name to use
	 *
	 * @param	string	config key to retrieve
	 * @param	mixed	default value in case the key doesn't exist
	 *
	 * @return  mixed
	 */
	public static function get_config($key, $default = null)
	{
		$class = get_called_class();

		// connection for this class is unknown
		if ( ! array_key_exists($class, static::$_config_cached) )
		{
			// no, so create it
			static::$_config_cached[$class] = Platform::get_config(isset(static::$_config)?static::$_config:array());

			// also fetch this models extensions
			list(static::$_methods_cached[$class], static::$_static_methods_cached[$class]) = Platform::get_extensions(static::get_config('overload_core'));
		}

		return isset(static::$_config_cached[$class][$key]) ? static::$_config_cached[$class][$key] : $default;
	}

	/**
	 * Fetch the database connection name to use
	 *
	 * @return  null|string
	 */
	public static function connection()
	{
		$class = get_called_class();

		// connection for this class is unknown
		if ( ! array_key_exists($class, static::$_connection_cached) )
		{
			// so create it
			static::$_connection_cached[$class] = Platform::get_connection(static::get_config('database'));
		}

		return static::$_connection_cached[$class];
	}

	/**
	 * Get the table name for this class
	 *
	 * @return  string
	 */
	public static function table()
	{
		$class = get_called_class();

		// table name for this class is unknown
		if ( ! array_key_exists($class, static::$_table_names_cached) )
		{
			// check if a table name was defined in the model
			if ( property_exists($class, '_table_name') )
			{
				// yep, so use that
				static::$_table_names_cached[$class] = static::$_table_name;
			}
			else
			{
				// nope, so make one up
				strpos($class, static::get_config('model_prefix')) === 0 and $class = substr($class, strlen(static::get_config('model_prefix')));
				static::$_table_names_cached[$class] = static::get_config('table_prefix').Platform::pluralize($class);
			}
		}

		return static::$_table_names_cached[$class];
	}

	/**
	 * Get the primary key(s) of this class
	 *
	 * @return  array
	 */
	public static function primary_key()
	{
		return static::$_primary_key;
	}

	/**
	 * Get the class's properties
	 *
	 * @return  array
	 */
	public static function properties()
	{
		$class = get_called_class();

		// If already determined
		if ( array_key_exists($class, static::$_properties_cached) )
		{
			return static::$_properties_cached[$class];
		}

		// Try to grab the properties from the class...
		if ( property_exists($class, '_properties') )
		{
			$properties = static::$_properties;
			foreach ($properties as $key => $p)
			{
				if ( is_string($p) )
				{
					unset($properties[$key]);
					$properties[$p] = array();
				}
			}
		}

		// ... if the above failed, check if we have a cached version lying around
		if ( empty($properties) )
		{
			$properties = Platform::get_properties(static::table());
		}

		// ... and if the above failed, fetch the properties from the DB
		if ( empty($properties) )
		{
			try
			{
				$properties = static::connection()->listFields(static::table());
			}
			catch ( \Exception $e )
			{
				throw new \Datamapper\Exceptions\DatamapperException('Listing columns failed, you have to set the model properties with a '.
					'static $_properties setting in the model. Original exception: '.$e->getMessage());
			}
		}

		// cache the properties for next usage
		static::$_properties_cached[$class] = $properties;

		return static::$_properties_cached[$class];
	}

	/**
	 * Fetches a property description array, or specific data from it
	 *
	 * @param   string  property or property.key
	 * @param   mixed   return value when key not present
	 * @return  mixed
	 */
	public static function property($key, $default = null)
	{
		$class = get_called_class();

		// If already determined
		if ( ! array_key_exists($class, static::$_properties_cached) )
		{
			static::properties();
		}

		return isset(static::$_properties_cached[$class][$key]) ? static::$_properties_cached[$class][$key] : $default;
	}

	/**
	 * Get the class's relations
	 *
	 * @param   string
	 * @return  array
	 */
	public static function relations($specific = false)
	{
		$class = get_called_class();

		if ( ! array_key_exists($class, static::$_relations_cached) )
		{
			$relations = array();
			foreach ( static::$_valid_relations as $rel_name => $rel_class )
			{
				if ( property_exists($class, '_'.$rel_name) )
				{
					foreach ( static::${'_'.$rel_name} as $key => $settings )
					{
						$name = is_string($settings) ? $settings : $key;
						$settings = is_array($settings) ? $settings : array();
						$relations[$name] = new $rel_class($class, $name, $settings);
					}
				}
			}

			static::$_relations_cached[$class] = $relations;
		}

		if ( $specific === false )
		{
			return static::$_relations_cached[$class];
		}
		else
		{
			if ( ! array_key_exists($specific, static::$_relations_cached[$class]) )
			{
				return false;
			}

			return static::$_relations_cached[$class][$specific];
		}
	}

	/**
	 * Get the class's observers and what they observe
	 *
	 * @param   string  specific observer to retrieve info of, allows direct param access by using dot notation
	 * @param   mixed   default return value when specific key wasn't found
	 * @return  array
	 */
	public static function observers($specific = null, $default = null)
	{
		$class = get_called_class();

		if ( ! array_key_exists($class, static::$_observers_cached) )
		{
			$observers = array();
			if ( property_exists($class, '_observers') )
			{
				foreach ( static::$_observers as $obs_k => $obs_v )
				{
					if ( is_int($obs_k) )
					{
						$observers[$obs_v] = array();
					}
					else
					{
						if ( is_string($obs_v) or (is_array($obs_v) and is_int(key($obs_v))) )
						{
							$observers[$obs_k] = array('events' => (array) $obs_v);
						}
						else
						{
							$observers[$obs_k] = $obs_v;
						}
					}
				}
			}
			static::$_observers_cached[$class] = $observers;
		}

		if ( $specific )
		{
			// @TODO: specific access inplementation
			// return \Arr::get(static::$_observers_cached[$class], $specific, $default);
		}

		return static::$_observers_cached[$class];
	}

	/**
	 * Register an observer
	 *
	 * @param	string	class name of the observer (including namespace)
	 * @param	mixed	observer options
	 *
	 * @return	void
	 */
	public static function register_observer($name, $options = null)
	{
		$class = get_called_class();
		$new_observer = is_null($options) ? array($name) : array($name => $options);

		static::$_observers_cached[$class] = static::observers() + $new_observer;
	}

	/**
	 * Unregister an observer
	 *
	 * @param string class name of the observer (including namespace)
	 * @return void
	 */
	public static function unregister_observer($name)
	{
		$class = get_called_class();
		foreach ( static::observers() as $key => $value )
		{
			if ( (is_array($value) and $key == $name) or $value == $name )
			{
				unset(static::$_observers_cached[$class][$key]);
			}
		}
	}

	/***************************************************************************
	 * Dynamic usage
	 ***************************************************************************/

	/**
	 * @var	\Cabinet\DBAL\Connection	current DBAL query object
	 */
	protected $_query = null;

	/**
	 * @var	Core_DatasetIterator	current iterator object
	 */
	protected $_iterator = null;

	/**
	 * @var	bool	keeps track of whether it's a new object
	 */
	protected $_is_new = true;

	/**
	 * @var	bool	keeps to object frozen to prevent updates
	 */
	protected $_frozen = false;

	/**
	 * @var	array	keeps the current state of all retrieved objects
	 */
	protected $_data = array();

	/**
	 * @var	array	keeps the current state of the related objects
	 */
	protected $_related_data = array();

	/**
	 * @var	array	keeps a copy of the current object as it was retrieved from the database
	 */
	protected $_original = array();

	/**
	 * @var	array	keeps a copy of the relation ids that were originally retrieved from the database
	 */
	protected $_related_original = array();

	/**
	 * @var	array	keeps track of relations that need to be disconnected before saving the new ones
	 */
	protected $_related_deleted = array();

	/**
	 * Constructor
	 *
	 * @param  array
	 * @param  bool
	 */
	public function __construct($data = array(), $new = true)
	{
		// this is to deal with PHP's native hydration from that happens
		// before the constructor is called, to _data is already populated
		if( ! empty($this->_data) )
		{
			throw new \Exception('TODO: deal with PHP native hydration!');
			$this->_original = $this->_data;
			$new = false;
		}

		// new empty model object
		if ( $new !== false )
		{
			// fetch the models properties
			$properties = $this->properties();

			// create the data storage for this object
			$this->_data[0] = array();

			// loop through the defined properties
			foreach ($properties as $prop => $settings)
			{
				// was a value for this property passed to the constructor?
				if (array_key_exists($prop, $data))
				{
					// assign it to this objects data storage
					$this->_data[0][$prop] = $data[$prop];
				}

				// if not, was a default value defined for this property?
				elseif (array_key_exists('default', $settings))
				{
					// assign it to this objects data storage
					$this->_data[0][$prop] = $settings['default'];
				}
			}

			// this is a fresh data object
			$this->_is_new = true;

			// call the defined after_create observers
			$this->observe('after_create');
		}

		// existing model data
		else
		{
			// sync the original data with the passed data
			$this->_update_original($data);

			// and merge it with the loaded data
			$this->_data[0] = array_merge($this->_data[0], $data);

			// this is an existing data object
			$this->_is_new = false;

			// call the defined after_load observers
			$this->observe('after_load');
		}
	}

	/**
	 * method calls magic method, allows for dynamic method extensions
	 *
	 * @param	string	config key to retrieve
	 * @param	mixed	default value in case the key doesn't exist
	 *
	 * @return  mixed
	 */
	public function __call($method, $args)
	{
		// get this class name
		$class = get_class($this);

		// do we have a method by this name?
		if ( empty(static::$_methods_cached[$class][$method]) )
		{
			// allow for dynamic getters, setters and unsetters
			if (substr($method, 0, 4) == 'get_')
			{
				return $this->get(substr($method, 4));
			}
			elseif (substr($method, 0, 4) == 'set_')
			{
				return $this->set(substr($method, 4), reset($args));
			}
			elseif (substr($method, 0, 6) == 'unset_')
			{
				return $this->__unset(substr($method, 6));
			}
			else
			{
				throw new \Datamapper\Exceptions\DatamapperException('unknown method "'.$method.'" called');
			}
		}

		// push the current object on the arguments stack
		array_unshift($args, $this);

		// call the extension method and return the result
		return call_user_func_array(static::$_methods_cached[$class][$method].'::'.$method, $args);
	}

	/**
	 * Fetch a property or relation
	 *
	 * @param	string	$property	the name of the property or relation to fetch
	 *
	 * @return	mixed
	 */
	public function & __get($property)
	{
		// check the loaded data first
		if ( isset($this->_data[0]) and array_key_exists($property, $this->_data[0]) )
		{
			return $this->_data[0][$property];
		}

		// maybe it's a relation?
		elseif ( $rel = static::relations($property) )
		{
			if ( ! array_key_exists($property, $this->_data_relations) )
			{
				if ( $this->is_new() )
				{
					$this->_data_relations[$property] = $rel->singular ? null : array();
				}
				else
				{
					$this->_data_relations[$property] = $rel->get($this);
				}

				$this->_update_original_relations(array($property));
			}
			return $this->_data_relations[$property];
		}

		// something odd, complain about it
		else
		{
			throw new \OutOfBoundsException('Property "'.$property.'" not found for '.get_called_class().'.');
		}
	}

	/**
	 * Set a property or relation
	 *
	 * @param	string	$property	the name of the property or relation to set
	 * @param	mixed	$value		value to set
	 *
	 * @return	\Datamapper\Core\Model for chaining
	 */
	public function __set($property, $value)
	{
		// you can't change an object in frozen state
		if ( $this->_frozen )
		{
			throw new FrozenObject('No changes allowed.');
		}

		// if an array of properties is passed, iterate over it
		if ( is_array($property) )
		{
			foreach ( $property as $p => $v )
			{
				$this->set($p, $v);
			}
		}
		else
		{
			// no array, so we need 2 arguments
			if ( func_num_args() < 2 )
			{
				throw new \InvalidArgumentException('You need to pass both a property name and a value to set().');
			}

			// you can not alter the primary keys of existing records
			if ( in_array($property, static::primary_key()) and $this->{$property} !== null )
			{
				throw new \FuelException('Primary key cannot be changed.');
			}

			// if it's a property, set it directly
			if ( array_key_exists($property, static::properties()) )
			{
				$this->_data[0][$property] = $value;
			}

			// if it's a relation, deal with it
			elseif ( static::relations($property) )
			{
				$this->is_fetched($property) or $this->_reset_relations[$property] = true;
				$this->_data_relations[$property] = $value;
			}

			// assume it's a non-column property
			else
			{
				$this->_data[0][$property] = $value;
			}
		}

		return $this;
	}

	/**
	 * Check whether a property exists, only return true for table columns and relations
	 *
	 * @param	string	$property	the name of the property or relation to check
	 *
	 * @return	bool
	 */
	public function __isset($property)
	{
		// check the loaded data first
		if ( isset($this->_data[0]) and array_key_exists($property, $this->_data[0]) )
		{
			return true;
		}

		// if not present, is it a table column?
		elseif ( array_key_exists($property, static::properties()) )
		{
			return true;
		}

		// if not, perhaps it's a relation
		elseif ( static::relations($property) )
		{
			return true;
		}

		// nope, don't know this one...
		return false;
	}

	/**
	 * Empty a property or reset a relation
	 *
	 * @param	string	$property	the name of the property or relation to unset
	 */
	public function __unset($property)
	{
		// check the loaded data first
		if ( isset($this->_data[0]) and array_key_exists($property, $this->_data[0]) )
		{
			if ( array_key_exists($property, static::properties()) )
			{
				$this->_data[$property] = null;
			}
			else
			{
				unset($this->_data[0][$property]);
			}
		}

		// check if it's a relation
		elseif ( $rel = static::relations($property) )
		{
			$this->_data_relations[$property] = $rel->singular ? null : array();
		}
	}

	/**
	 * Returns the models current query object. Will create one if needed
	 *
	 * @return	\Cabinet\DBAL\DB	current query object
	 */
	public function query($reset = false)
	{
		if ( $reset or ! is_object($this->_query) )
		{
			$this->_query = static::connection()->select()->from(static::table());
		}

		return $this->_query;
	}

	/**
	 * Returns the models current query object. Will create one if needed
	 *
	 * @param	array	$records	result of a Cabinet DBAL query
	 *
	 * @return	\Datamapper\Core\Model	current object, for chaining
	 */
	public function hydrate(array $records = array())
	{
		// reset the data in the current object
		$this->_data = $records;
		$this->_original = isset($this->_data[0]) ? $this->_data[0] : array();

		return $this;
	}

	/**
	 * Calls all observers for the current event
	 *
	 * @param	string
	 */
	public function observe($event)
	{
		// loop through the defined observers for this model
		foreach ( static::observers() as $observer => $settings )
		{
			// get the events defined for this observer
			$events = isset($settings['events']) ? $settings['events'] : array();

			// if no specific events defined (defaults to all) or the called event is defined
			if ( empty($events) or in_array($event, $events) )
			{
				// find the defined observer class
				if ( ! class_exists($observer) )
				{
					// not found by full class name, see if it uses Observer_classname syntax
					$observer_class = Platform::get_namespace($observer).'Observer_'.Platform::denamespace($observer);

					// try again
					if ( ! class_exists($observer_class) )
					{
						// not found again, so bail out
						throw new \UnexpectedValueException($observer);
					}

					// add the observer back to the observer cache with the full classname so we don't have to search next time
					unset(static::$_observers_cached[$observer]);
					static::$_observers_cached[$observer_class] = $events;
					$observer = $observer_class;
				}

				// call the defined observer
				try
				{
					call_user_func(array($observer, 'orm_notify'), $this, $event);
				}
				catch (\Exception $e)
				{
					// make sure the object is unfrozen before bailing out
					$this->unfreeze();

					// rethrow the exception
					throw $e;
				}
			}
		}
	}

	/**
	 * Return the contents of the current object as an array
	 *
	 * @return  array
	 */
	public function to_array()
	{
		return $this->_data[0];
	}

	/**
	 * Return all the contents of the current object as an array
	 *
	 * @return  array
	 */
	public function all_to_array()
	{
		return $this->_data;
	}

	/**
	 * Update the original setting for this object
	 *
	 * @param  array|null  $original
	 */
	public function _update_original($original = null)
	{
		$original = is_null($original) ? $this->_data : $original;
		$this->_original = array_merge($this->_original, $original);

		$this->_update_original_relations();
	}

	/**
	 * Update the original relations for this object
	 */
	public function _update_original_relations($relations = null)
	{
		if ( is_null($relations) )
		{
			$this->_original_relations = array();
			$relations = $this->_data_relations;
		}
		else
		{
			foreach ( $relations as $key => $rel )
			{
				// Unload the just fetched relation from the originals
				unset($this->_original_relations[$rel]);

				// Unset the numeric key and set the data to update by the relation name
				unset($relations[$key]);
				$relations[$rel] = $this->_data_relations[$rel];
			}
		}

		foreach ( $relations as $rel => $data )
		{
			if ( is_array($data) )
			{
				$this->_original_relations[$rel] = array();
				foreach ( $data as $obj )
				{
					$this->_original_relations[$rel][] = $obj ? $obj->implode_pk($obj) : null;
				}
			}
			else
			{
				$this->_original_relations[$rel] = $data ? $data->implode_pk($data) : null;
			}
		}
	}

	/***************************************************************************
	 * ArrayAccess methods
	 ***************************************************************************/

	public function offsetSet($offset, $value)
	{
		throw new \Datamapper\Exceptions\DatamapperException('You can not modify a Datamapper object using array access');
	}

	public function offsetExists($offset)
	{
		// does the requested offset exist?
		return isset($this->_data[$offset]);
	}

	public function offsetUnset($offset)
	{
		// does the requested offset exist?
		if ( isset($this->_data[$offset]) )
		{
			// remove the data element
			unset($this->_data[$offset]);

			// reindex the keys
			$this->_data = array_values($this->_data);

			// if the first one was removed, also reset the original data
			$offset === 0 and $this->_original = array();
		}
		else
		{
			// nope...
			return false;
		}
	}

	public function offsetGet($offset)
	{
		// does the requested offset exist?
		if ( isset($this->_data[$offset]) )
		{
			// dehydrate it and return the object
			$class = get_class($this);
			return new $class($this->_data[$offset]);
		}
		else
		{
			// nope...
			return null;
		}
	}

	/***************************************************************************
	 * IteratorAggregate methods
	 ***************************************************************************/

	/**
	 * allows the all array to be iterated over without having to specify it
	 *
	 * @return	Iterator	An iterator for the all array
	 */
	public function getIterator()
	{
		// do we have an iterator object defined?
		if ( ! $this->_iterator instanceOf DatasetIterator )
		{
			$this->_iterator = new DatasetIterator($this, $this->_data);
		}

		return $this->_iterator;
	}

}
