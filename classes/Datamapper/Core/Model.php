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

use \Datamapper\Platform\Platform as Platform;

/**
 * Datamapper Model base class
 */
class Model
{
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
	 * @var  string  connection to use
	 */
	protected static $_connection_cached = array();

	/**
	 * @var  array  configuration for this model
	 */
	protected static $_config_cached = array();

	/**
	 * @var  array  cached tables
	 */
	protected static $_table_names_cached = array();

	/**
	 * @var  array  cached properties
	 */
	protected static $_properties_cached = array();

	/**
	 * @var  string  relationships
	 */
	protected static $_relations_cached = array();

	/**
	 * @var  array  cached observers
	 */
	protected static $_observers_cached = array();

	/**
	 * @var  array  array of valid relation types
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
		var_dump($method, $args);
		die('this callStatic method is not implemented !');
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
				throw new Exceptions\DatamapperException('Listing columns failed, you have to set the model properties with a '.
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
	 * @var	bool	keeps track of whether it's a new object
	 */
	protected $_is_new = true;

	/**
	 * @var	bool	keeps to object frozen to prevent updates
	 */
	protected $_frozen = false;

	/**
	 * @var	array	keeps the current state of the object
	 */
	protected $_data = array();

	/**
	 * @var	array	keeps the current state of the related objects
	 */
	protected $_related_data = array();

	/**
	 * @var	array	keeps a copy of the object as it was retrieved from the database
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
			$this->_original = $this->_data;
			$new = false;
		}

		// new empty model object
		if ( $new !== false )
		{
			// fetch the models properties
			$properties = $this->properties();

			// loop through the defined properties
			foreach ($properties as $prop => $settings)
			{
				// was a value for this property passed to the constructor?
				if (array_key_exists($prop, $data))
				{
					// assign it to this objects data storage
					$this->_data[$prop] = $data[$prop];
				}

				// if not, was a default value defined for this property?
				elseif (array_key_exists('default', $settings))
				{
					// assign it to this objects data storage
					$this->_data[$prop] = $settings['default'];
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
			$this->_data = array_merge($this->_data, $data);

			// this is an existing data object
			$this->_is_new = false;

			// call the defined after_load observers
			$this->observe('after_load');
		}
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

}
